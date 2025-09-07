<?php

namespace App\Services;

use App\\Filament\\Imports\\SubCategoryImporter;
use App\Models\SubCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SubCategoryImportExportService
{
    /**
     * Import subcategories from Excel file
     */
    public function importSubCategories(UploadedFile $file, bool $updateExisting = false): array
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
            $importer = new SubCategoryImporter($updateExisting);

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
     * Export subcategories to Excel
     */
    public function exportSubCategories(array $subCategoryIds = null): string
    {
        $export = new SubCategoryExport($subCategoryIds);
        $filename = 'subcategories_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        Excel::store($export, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }

    /**
     * Generate Excel template for import
     */
    public function generateTemplate(): string
    {
        $template = new SubCategoryImportTemplate();
        $filename = 'subcategory_import_template.xlsx';
        
        Excel::store($template, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }

    /**
     * Validate Excel file structure
     */
    public function validateFileStructure(UploadedFile $file): array
    {
        try {
            $data = Excel::toArray(new SubCategoryImporter(), $file);
            
            if (empty($data) || empty($data[0])) {
                return [
                    'valid' => false,
                    'errors' => ['File is empty or corrupted']
                ];
            }

            $headers = array_keys($data[0][0] ?? []);
            $expectedHeaders = SubCategoryImporter::getExpectedHeaders();
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
 * SubCategory Export Class
 */
class SubCategoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $subCategoryIds;

    public function __construct(array $subCategoryIds = null)
    {
        $this->subCategoryIds = $subCategoryIds;
    }

    public function collection()
    {
        $query = SubCategory::with('category')->withCount('products');
        
        if ($this->subCategoryIds) {
            $query->whereIn('id', $this->subCategoryIds);
        }
        
        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Sub Kategori',
            'Photo',
            'Kategori',
            'Jumlah Produk',
            'Tanggal Dibuat',
            'Terakhir Update'
        ];
    }

    public function map($subCategory): array
    {
        return [
            $subCategory->id,
            $subCategory->name,
            $subCategory->photo ?? '',
            $subCategory->category?->name ?? '',
            $subCategory->products_count,
            $subCategory->created_at?->format('Y-m-d H:i:s'),
            $subCategory->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
            
            // Set auto width for all columns
            'A:G' => ['alignment' => ['wrapText' => true]]
        ];
    }
}

/**
 * SubCategory Import Template Class
 */
class SubCategoryImportTemplate implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // Return empty collection with sample data
        return collect([
            [
                'Kamera DSLR',
                'https://example.com/photo.jpg',
            ]
        ]);
    }

    public function headings(): array
    {
        return SubCategoryImporter::getExpectedHeaders();
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
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
     
        return [];
    }
}
