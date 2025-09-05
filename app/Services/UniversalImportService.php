<?php

namespace App\Services;

use App\Exports\FailedImportExport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class UniversalImportService
{
    /**
     * Import data using specified importer class
     */
    public function importData(string $importerClass, UploadedFile $file, bool $updateExisting = false): array
    {
        try {
            // Validate file type
            $allowedMimeTypes = [
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv'
            ];

            if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
                throw new \Exception('File type not supported. Please use Excel (.xls, .xlsx) or CSV files.');
            }

            // Check if importer class exists
            if (!class_exists($importerClass)) {
                throw new \Exception("Importer class {$importerClass} not found.");
            }

            // Create importer instance
            $importer = new $importerClass($updateExisting);

            // Import the file
            Excel::import($importer, $file);

            // Get import results
            return $importer->getImportResults();

        } catch (\Exception $e) {
            return [
                'total' => 0,
                'success' => 0,
                'failed' => 0,
                'updated' => 0,
                'errors' => ['Error: ' . $e->getMessage()],
                'failed_rows' => []
            ];
        }
    }

    /**
     * Export failed import rows to Excel
     */
    public function exportFailedImport(array $failedRows, string $importType = 'data'): string
    {
        if (empty($failedRows)) {
            throw new \Exception('No failed rows to export');
        }

        $export = new FailedImportExport($failedRows, $importType);
        $filename = 'failed_import_' . $importType . '_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        Excel::store($export, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }

    /**
     * Generate preview of import data
     */
    public function previewImport(string $importerClass, UploadedFile $file, int $maxRows = 10): array
    {
        try {
            // Check if importer class exists
            if (!class_exists($importerClass)) {
                throw new \Exception("Importer class {$importerClass} not found.");
            }

            $data = Excel::toArray(new $importerClass(), $file);
            
            if (empty($data) || empty($data[0])) {
                return [
                    'success' => false,
                    'error' => 'File is empty or corrupted'
                ];
            }

            $headers = array_keys($data[0][0] ?? []);
            $rows = array_slice($data[0], 0, $maxRows);
            
            // Validate structure
            $validation = $this->validateFileStructure($importerClass, $file);
            
            return [
                'success' => true,
                'headers' => $headers,
                'sample_rows' => $rows,
                'total_rows' => count($data[0]),
                'validation' => $validation,
                'expected_headers' => $this->getExpectedHeaders($importerClass)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Preview error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate Excel file structure
     */
    public function validateFileStructure(string $importerClass, UploadedFile $file): array
    {
        try {
            // Check if importer class exists
            if (!class_exists($importerClass)) {
                throw new \Exception("Importer class {$importerClass} not found.");
            }

            $data = Excel::toArray(new $importerClass(), $file);
            
            if (empty($data) || empty($data[0])) {
                return [
                    'valid' => false,
                    'errors' => ['File is empty or corrupted']
                ];
            }

            $headers = array_keys($data[0][0] ?? []);
            $expectedHeaders = $this->getExpectedHeaders($importerClass);
            $missingHeaders = array_diff($expectedHeaders, $headers);

            if (!empty($missingHeaders)) {
                return [
                    'valid' => false,
                    'errors' => [
                        'Missing required columns: ' . implode(', ', $missingHeaders),
                        'Expected columns: ' . implode(', ', $expectedHeaders)
                    ]
                ];
            }

            return [
                'valid' => true,
                'total_rows' => count($data[0]) - 1, // -1 for header
                'headers' => $headers
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => ['File validation error: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Generate Excel template for import
     */
    public function generateTemplate(string $templateClass): string
    {
        if (!class_exists($templateClass)) {
            throw new \Exception("Template class {$templateClass} not found.");
        }

        $template = new $templateClass();
        $filename = 'import_template_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        Excel::store($template, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }

    /**
     * Get expected headers from importer class
     */
    protected function getExpectedHeaders(string $importerClass): array
    {
        if (method_exists($importerClass, 'getExpectedHeaders')) {
            return $importerClass::getExpectedHeaders();
        }

        return [];
    }

    /**
     * Create a generic failed import export for any data structure
     */
    public function createGenericFailedExport(array $failedRows, array $headers, string $importType): string
    {
        if (empty($failedRows)) {
            throw new \Exception('No failed rows to export');
        }

        $export = new GenericFailedImportExport($failedRows, $headers, $importType);
        $filename = 'failed_import_' . $importType . '_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        Excel::store($export, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }
}
