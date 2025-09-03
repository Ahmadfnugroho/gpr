<?php

namespace App\Services;

use App\Imports\CustomerImporter;
use App\Models\Customer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CustomerImportExportService
{
    /**
     * Import customers from Excel file
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
     */
    public function validateFileStructure(UploadedFile $file): array
    {
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
            'Facebook',
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
            $customer->facebook_username,
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
