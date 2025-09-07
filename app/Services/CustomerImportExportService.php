<?php

namespace App\Services;

use App\Filament\Imports\CustomerImporter;
use App\Models\Customer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerImportExportService
{
    /**
     * Import customers from Excel file (Async)
     */
    public function importCustomersAsync(UploadedFile $file, bool $updateExisting = false, ?int $userId = null): array
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

            // Validate file size (max 10MB for production)
            if ($file->getSize() > 10 * 1024 * 1024) {
                throw new \Exception('File too large. Maximum size is 10MB.');
            }

            // Store file temporarily
            $importId = uniqid('import_');
            $fileName = $importId . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('imports', $fileName, 'local');

            // Dispatch job to queue
            \App\Jobs\ImportCustomersJob::dispatch($filePath, $updateExisting, $userId, $importId)
                ->onQueue('imports')
                ->delay(now()->addSeconds(2)); // Small delay to ensure response is sent first

            return [
                'queued' => true,
                'import_id' => $importId,
                'message' => 'Import job has been queued. You will be notified when it completes.',
                'estimated_time' => '2-5 minutes for large files'
            ];

        } catch (\Exception $e) {
            return [
                'queued' => false,
                'error' => $e->getMessage(),
                'message' => 'Failed to queue import job'
            ];
        }
    }

    /**
     * Import customers from Excel file (Sync) - for small files only
     */
    public function importCustomers(UploadedFile $file, bool $updateExisting = false): array
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

            // Remove file size limitation - handle ALL files synchronously
            // Progressive limits based on file size
            $fileSize = $file->getSize();
            $estimatedRows = intval($fileSize / 250); // Rough estimate: 250 bytes per row
            
            Log::info('Starting optimized sync import', [
                'file_size' => $fileSize,
                'estimated_rows' => $estimatedRows,
                'file_name' => $file->getClientOriginalName()
            ]);

            // Set progressive limits (already set in controller, but backup here)
            if ($fileSize > 2 * 1024 * 1024) { // > 2MB
                ini_set('memory_limit', '1G');
                ini_set('max_execution_time', '600');
                set_time_limit(600);
            } elseif ($fileSize > 1024 * 1024) { // > 1MB  
                ini_set('memory_limit', '512M');
                ini_set('max_execution_time', '300');
                set_time_limit(300);
            } else {
                ini_set('memory_limit', '256M');
                ini_set('max_execution_time', '120');
                set_time_limit(120);
            }
            
            // Ignore user abort
            ignore_user_abort(true);

            // Create importer instance
            $importer = new CustomerImporter($updateExisting);

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
                'errors' => ['Error: ' . $e->getMessage()]
            ];
        }
    }

    /**
     * Get import results by import ID
     */
    public function getImportResults(string $importId): ?array
    {
        return cache()->get("customer_import_results_{$importId}");
    }

    /**
     * Check import status
     */
    public function getImportStatus(string $importId): array
    {
        $results = $this->getImportResults($importId);
        
        if (!$results) {
            // Check if job is still processing
            $queuedJobs = DB::table('jobs')
                ->where('payload', 'like', '%' . $importId . '%')
                ->count();
                
            $failedJobs = DB::table('failed_jobs')
                ->where('payload', 'like', '%' . $importId . '%')
                ->count();

            if ($queuedJobs > 0) {
                return [
                    'status' => 'processing',
                    'message' => 'Import is still being processed...'
                ];
            } elseif ($failedJobs > 0) {
                return [
                    'status' => 'failed',
                    'message' => 'Import job failed. Please try again.'
                ];
            } else {
                return [
                    'status' => 'not_found',
                    'message' => 'Import not found or expired.'
                ];
            }
        }

        return [
            'status' => 'completed',
            'results' => $results['results'],
            'completed_at' => $results['completed_at']
        ];
    }

    /**
     * Export customers to Excel
     */
    public function exportCustomers(array $customerIds = null): string
    {
        $export = new CustomerExport($customerIds);
        $filename = 'customers_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        Excel::store($export, $filename, 'public');

        return Storage::disk('public')->path($filename);
    }

    /**
     * Generate Excel template for import
     */
    public function generateTemplate(): string
    {
        $template = new CustomerImportTemplate();
        $filename = 'customer_import_template.xlsx';

        Excel::store($template, $filename, 'public');

        return Storage::disk('public')->path($filename);
    }

    /**
     * Validate Excel file structure
     * TEMPORARILY DISABLED - Excel package not properly installed
     */
    public function validateFileStructure(UploadedFile $file): array
    {
        return [
            'valid' => false,
            'errors' => ['Excel validation temporarily disabled - package not properly installed']
        ];

        /* ORIGINAL CODE - COMMENTED OUT UNTIL EXCEL PACKAGE IS FIXED
        try {
            $data = Excel::toArray(new CustomerImporter(), $file);
            
            if (empty($data) || empty($data[0])) {
                return [
                    'valid' => false,
                    'errors' => ['File is empty or corrupted']
                ];
            }

            $headers = array_keys($data[0][0] ?? []);
            $expectedHeaders = CustomerImporter::getExpectedHeaders();
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
        */
    }
}

/**
 * Customer Export Class
 */
class CustomerExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $customerIds;

    public function __construct(array $customerIds = null)
    {
        $this->customerIds = $customerIds;
    }

    public function collection()
    {
        $query = Customer::with(['customerPhoneNumbers']);

        if ($this->customerIds) {
            $query->whereIn('id', $this->customerIds);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Lengkap',
            'Email',
            'Nomor HP 1',
            'Nomor HP 2',
            'Jenis Kelamin',
            'Status',
            'Alamat',
            'Pekerjaan',
            'Alamat Kantor',
            'Instagram',
            'Kontak Emergency',
            'HP Emergency',
            'Sumber Info',
            'Tanggal Dibuat',
            'Terakhir Update'
        ];
    }

    public function map($customer): array
    {
        $phoneNumbers = $customer->customerPhoneNumbers->pluck('phone_number')->toArray();

        return [
            $customer->id,
            $customer->name,
            $customer->email,
            $phoneNumbers[0] ?? '',
            $phoneNumbers[1] ?? '',
            $customer->gender,
            $customer->status,
            $customer->address,
            $customer->job,
            $customer->office_address,
            $customer->instagram_username,
            $customer->emergency_contact_name,
            $customer->emergency_contact_number,
            $customer->source_info,
            $customer->created_at?->format('Y-m-d H:i:s'),
            $customer->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],

            // Set auto width for all columns
            'A:Q' => ['alignment' => ['wrapText' => true]]
        ];
    }
}

/**
 * Customer Import Template Class
 */
class CustomerImportTemplate implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // Return empty collection with sample data
        return collect([
            [
                'John Doe',
                'john@example.com',
                '081234567890',
                '087654321098',
                'male',
                'active',
                'Jl. Contoh No. 123, Jakarta',
                'Software Developer',
                'Jl. Kantor No. 456, Jakarta',
                'johndoe',
                'john.doe',
                'Jane Doe',
                '081987654321',
                'Website'
            ]
        ]);
    }

    public function headings(): array
    {
        return CustomerImporter::getExpectedHeaders();
    }

    public function styles(Worksheet $sheet)
    {
        // Set header row style
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '4CAF50']
            ]
        ]);

        // Set sample data row style
        $sheet->getStyle('A2:N2')->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'E8F5E8']
            ]
        ]);

        // Auto-size columns
        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add data validation for gender column (E)
        $validation = $sheet->getCell('E2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Input error');
        $validation->setError('Value is not in list.');
        $validation->setPromptTitle('Pick from list');
        $validation->setPrompt('Please pick a value from the drop-down list.');
        $validation->setFormula1('"male,female"');

        // Add data validation for status column (F)
        $statusValidation = $sheet->getCell('F2')->getDataValidation();
        $statusValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $statusValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $statusValidation->setAllowBlank(true);
        $statusValidation->setShowInputMessage(true);
        $statusValidation->setShowErrorMessage(true);
        $statusValidation->setShowDropDown(true);
        $statusValidation->setErrorTitle('Input error');
        $statusValidation->setError('Value is not in list.');
        $statusValidation->setPromptTitle('Pick from list');
        $statusValidation->setPrompt('Please pick a value from the drop-down list.');
        $statusValidation->setFormula1('"active,inactive,blacklist"');

        return [];
    }
}
