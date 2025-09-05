<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MemoryOptimizedFailedImportExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Str;

trait EnhancedImportControllerTrait
{
    protected int $maxDisplayErrors = 500; // Limit displayed errors to prevent memory issues
    protected int $importChunkSize = 1000; // Process in chunks
    protected int $memoryLimit = 128; // Memory limit in MB

    /**
     * Enhanced import with memory optimization and detailed error tracking
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            'update_existing' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $updateExisting = $request->boolean('update_existing', false);
        
        // Check available memory before processing
        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
        if ($memoryUsage > ($this->memoryLimit * 0.8)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient memory to process large file. Please try with smaller file or contact administrator.'
            ], 507);
        }

        try {
            $results = $this->processImportFile($file, $updateExisting);
            
            if ($results['total_failed'] > 0) {
                // Store failed rows in session with memory optimization
                $this->storeFailedRowsInSession($results['failed_rows'], $results['expected_headers']);
                
                return response()->json([
                    'success' => false,
                    'message' => "Import completed with errors. {$results['total_successful']} rows successful, {$results['total_failed']} rows failed.",
                    'stats' => [
                        'total_rows' => $results['total_rows'],
                        'successful' => $results['total_successful'],
                        'failed' => $results['total_failed'],
                        'skipped' => $results['total_skipped'] ?? 0
                    ],
                    'has_errors' => true,
                    'view_errors_url' => route('import.view-errors'),
                    'download_failed_url' => route('import.download-failed')
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => "Import completed successfully. {$results['total_successful']} rows imported.",
                'stats' => [
                    'total_rows' => $results['total_rows'],
                    'successful' => $results['total_successful'],
                    'failed' => 0,
                    'skipped' => $results['total_skipped'] ?? 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process import file with memory optimization
     */
    protected function processImportFile($file, bool $updateExisting = false): array
    {
        $reader = IOFactory::createReader(IOFactory::identify($file->path()));
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file->path());
        $worksheet = $spreadsheet->getActiveSheet();
        
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        
        $results = [
            'total_rows' => 0,
            'total_successful' => 0,
            'total_failed' => 0,
            'total_skipped' => 0,
            'failed_rows' => [],
            'expected_headers' => $this->getExpectedHeaders()
        ];

        // Process in chunks to prevent memory exhaustion
        for ($startRow = 2; $startRow <= $highestRow; $startRow += $this->importChunkSize) {
            $endRow = min($startRow + $this->importChunkSize - 1, $highestRow);
            $chunk = $this->processChunk($worksheet, $startRow, $endRow, $highestColumn, $updateExisting);
            
            $results['total_rows'] += $chunk['processed'];
            $results['total_successful'] += $chunk['successful'];
            $results['total_failed'] += $chunk['failed'];
            $results['total_skipped'] += $chunk['skipped'];
            $results['failed_rows'] = array_merge($results['failed_rows'], $chunk['failed_rows']);
            
            // Memory cleanup after each chunk
            unset($chunk);
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            
            // Check memory usage and break if getting too high
            $memoryUsage = memory_get_usage(true) / 1024 / 1024;
            if ($memoryUsage > $this->memoryLimit) {
                break;
            }
        }

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $results;
    }

    /**
     * Process a chunk of rows
     */
    protected function processChunk($worksheet, int $startRow, int $endRow, string $highestColumn, bool $updateExisting): array
    {
        $chunkResults = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'failed_rows' => []
        ];

        for ($row = $startRow; $row <= $endRow; $row++) {
            $rowData = [];
            $hasData = false;

            // Read row data
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cellValue = $worksheet->getCell($col . $row)->getCalculatedValue();
                if (!empty($cellValue)) {
                    $hasData = true;
                }
                $rowData[] = $cellValue;
            }

            if (!$hasData) {
                $chunkResults['skipped']++;
                continue;
            }

            $chunkResults['processed']++;

            try {
                $mappedData = $this->mapRowData($rowData);
                $validationResult = $this->validateRowData($mappedData, $row);

                if (!$validationResult['valid']) {
                    $chunkResults['failed']++;
                    $chunkResults['failed_rows'][] = [
                        'row_number' => $row,
                        'row_data' => $mappedData,
                        'error_reason' => $validationResult['error'],
                        'validation_errors' => $validationResult['validation_errors'] ?? []
                    ];
                    continue;
                }

                // Check for existing record
                if (!$updateExisting && $this->recordExists($mappedData)) {
                    $chunkResults['failed']++;
                    $chunkResults['failed_rows'][] = [
                        'row_number' => $row,
                        'row_data' => $mappedData,
                        'error_reason' => 'Data sudah ada dalam database',
                        'validation_errors' => []
                    ];
                    continue;
                }

                // Save or update record
                $this->saveRecord($mappedData, $updateExisting);
                $chunkResults['successful']++;

            } catch (\Exception $e) {
                $chunkResults['failed']++;
                $chunkResults['failed_rows'][] = [
                    'row_number' => $row,
                    'row_data' => $mappedData ?? [],
                    'error_reason' => 'Error: ' . $e->getMessage(),
                    'validation_errors' => []
                ];
            }
        }

        return $chunkResults;
    }

    /**
     * Store failed rows in session with memory optimization
     */
    protected function storeFailedRowsInSession(array $failedRows, array $expectedHeaders): void
    {
        // Store full dataset for download
        session(['import_failed_rows_full' => $failedRows]);
        
        // Store limited dataset for display (prevent memory issues)
        $limitedFailedRows = array_slice($failedRows, 0, $this->maxDisplayErrors);
        session([
            'import_failed_rows_limited' => $limitedFailedRows,
            'import_expected_headers' => $expectedHeaders,
            'import_total_failed' => count($failedRows),
            'import_display_limited' => count($failedRows) > $this->maxDisplayErrors
        ]);
    }

    /**
     * View all import errors with memory-safe pagination
     */
    public function viewAllErrors(Request $request)
    {
        $failedRows = session('import_failed_rows_limited', []);
        $expectedHeaders = session('import_expected_headers', []);
        $totalFailed = session('import_total_failed', 0);
        $isLimited = session('import_display_limited', false);

        if (empty($failedRows)) {
            return redirect()->back()->with('error', 'No import errors found in session.');
        }

        $perPage = 50; // Reduced for better performance
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedRows = array_slice($failedRows, $offset, $perPage);

        $pagination = [
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => min(count($failedRows), $this->maxDisplayErrors),
            'total_pages' => ceil(min(count($failedRows), $this->maxDisplayErrors) / $perPage),
            'has_more' => $isLimited && count($failedRows) >= $this->maxDisplayErrors,
            'actual_total' => $totalFailed
        ];

        return view('import.errors', [
            'failedRows' => $paginatedRows,
            'expectedHeaders' => $expectedHeaders,
            'pagination' => $pagination,
            'stats' => [
                'total_failed' => $totalFailed,
                'displayed_failed' => count($failedRows),
                'is_limited_display' => $isLimited
            ]
        ]);
    }

    /**
     * Download failed rows as Excel with memory optimization
     */
    public function downloadFailedRows()
    {
        $failedRows = session('import_failed_rows_full', []);
        $expectedHeaders = session('import_expected_headers', []);

        if (empty($failedRows)) {
            return redirect()->back()->with('error', 'No failed rows to download.');
        }

        try {
            $export = new MemoryOptimizedFailedImportExport(
                $failedRows, 
                $expectedHeaders,
                $this->importChunkSize
            );

            $fileName = 'failed_import_' . date('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download($export, $fileName);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate Excel file: ' . $e->getMessage());
        }
    }

    /**
     * Get template with memory-optimized structure
     */
    public function downloadTemplate()
    {
        try {
            $headers = $this->getExpectedHeaders();
            $sampleData = $this->getSampleData();

            // Create simple template to avoid memory issues
            $templateData = [
                $headers, // Header row
                ...$sampleData // Sample data rows
            ];

            $export = new class($templateData) implements \Maatwebsite\Excel\Concerns\FromArray {
                private $data;
                
                public function __construct($data) {
                    $this->data = $data;
                }
                
                public function array(): array {
                    return $this->data;
                }
            };

            $fileName = 'import_template_' . date('Y-m-d') . '.xlsx';
            
            return Excel::download($export, $fileName);

        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to generate template: ' . $e->getMessage());
        }
    }

    /**
     * Clear import session data
     */
    public function clearImportSession()
    {
        session()->forget([
            'import_failed_rows_full',
            'import_failed_rows_limited', 
            'import_expected_headers',
            'import_total_failed',
            'import_display_limited'
        ]);

        return response()->json(['success' => true, 'message' => 'Import session cleared']);
    }

    /**
     * Get current memory usage statistics
     */
    public function getMemoryStats()
    {
        $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
        $memoryPeak = memory_get_peak_usage(true) / 1024 / 1024; // MB
        $memoryLimit = ini_get('memory_limit');

        return response()->json([
            'current_usage_mb' => round($memoryUsage, 2),
            'peak_usage_mb' => round($memoryPeak, 2),
            'memory_limit' => $memoryLimit,
            'usage_percentage' => round(($memoryUsage / $this->memoryLimit) * 100, 2)
        ]);
    }

    // Abstract methods that need to be implemented by the using class
    abstract protected function getExpectedHeaders(): array;
    abstract protected function getSampleData(): array;
    abstract protected function mapRowData(array $rowData): array;
    abstract protected function validateRowData(array $data, int $rowNumber): array;
    abstract protected function recordExists(array $data): bool;
    abstract protected function saveRecord(array $data, bool $updateExisting): void;
}
