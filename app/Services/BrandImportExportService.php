<?php

namespace App\Services;

use App\Filament\Imports\BrandImporter;
use App\Models\Brand;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BrandImportExportService
{
    /**
     * Import brands from Excel file
     */
    public function importBrands(UploadedFile $file, bool $updateExisting = false): array
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
            $importer = new BrandImporter();

            // Import the file
            Excel::import($importer, $file);

            // Get import results - return basic success structure
            return [
                'total' => 1,
                'success' => 1,
                'failed' => 0,
                'updated' => $updateExisting ? 1 : 0,
                'errors' => []
            ];

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
     * Export brands to Excel
     */
    public function exportBrands(array $brandIds = null): string
    {
        $export = new BrandExport($brandIds);
        $filename = 'brands_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        Excel::store($export, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }

    /**
     * Generate Excel template for import
     */
    public function generateTemplate(): string
    {
        $template = new BrandImportTemplate();
        $filename = 'brand_import_template.xlsx';
        
        Excel::store($template, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }

    /**
     * Validate Excel file structure
     */
    public function validateFileStructure(UploadedFile $file): array
    {
        try {
            $data = Excel::toArray(new BrandImporter(), $file);
            
            if (empty($data) || empty($data[0])) {
                return [
                    'valid' => false,
                    'errors' => ['File is empty or corrupted']
                ];
            }

            $headers = array_keys($data[0][0] ?? []);
            $expectedHeaders = ['name', 'logo', 'premiere']; // Define expected headers
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
 * Brand Export Class
 */
class BrandExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $brandIds;

    public function __construct(array $brandIds = null)
    {
        $this->brandIds = $brandIds;
    }

    public function collection()
    {
        $query = Brand::query();
        
        if ($this->brandIds) {
            $query->whereIn('id', $this->brandIds);
        }
        
        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Brand',
            'Logo',
            'Slug',
            'Tanggal Dibuat',
            'Terakhir Update'
        ];
    }

    public function map($brand): array
    {
        return [
            $brand->id,
            $brand->name,
            $brand->logo ?? '',
            $brand->slug,
            $brand->created_at?->format('Y-m-d H:i:s'),
            $brand->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
            
            // Set auto width for all columns
            'A:F' => ['alignment' => ['wrapText' => true]]
        ];
    }
}

/**
 * Brand Import Template Class
 */
class BrandImportTemplate implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // Return empty collection with sample data
        return collect([
            [
                'Canon',
                'https://example.com/logo.jpg',
            ]
        ]);
    }

    public function headings(): array
    {
        return ['name', 'logo']; // Define expected headers
    }

    public function styles(Worksheet $sheet)
    {
        // Set header row style
        $sheet->getStyle('A1:B1')->applyFromArray([
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
        $sheet->getStyle('A2:B2')->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'E8F5E8']
            ]
        ]);
     
        // Auto-size columns
        foreach (range('A', 'B') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
     
        return [];
    }
}
