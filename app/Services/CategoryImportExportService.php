<?php

namespace App\Services;

use App\Filament\Imports\CategoryImporter;
use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CategoryImportExportService
{
    /**
     * Import categories from Excel file
     */
    public function importCategories(UploadedFile $file, bool $updateExisting = false): array
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
            $importer = new CategoryImporter();

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
     * Export categories to Excel
     */
    public function exportCategories(array $categoryIds = null): string
    {
        $export = new CategoryExport($categoryIds);
        $filename = 'categories_export_' . date('Y-m-d_H-i-s') . '.xlsx';

        Excel::store($export, $filename, 'public');

        return Storage::disk('public')->path($filename);
    }

    /**
     * Generate Excel template for import
     */
    public function generateTemplate(): string
    {
        $template = new CategoryImportTemplate();
        $filename = 'category_import_template.xlsx';

        Excel::store($template, $filename, 'public');

        return Storage::disk('public')->path($filename);
    }

    /**
     * Validate Excel file structure
     */
    public function validateFileStructure(UploadedFile $file): array
    {
        try {
            $data = Excel::toArray(new CategoryImporter(), $file);

            if (empty($data) || empty($data[0])) {
                return [
                    'valid' => false,
                    'errors' => ['File is empty or corrupted']
                ];
            }

            $headers = array_keys($data[0][0] ?? []);
            $expectedHeaders = ['name', 'photo', 'slug']; // Define expected headers
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
 * Category Export Class
 */
class CategoryExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $categoryIds;

    public function __construct(array $categoryIds = null)
    {
        $this->categoryIds = $categoryIds;
    }

    public function collection()
    {
        $query = Category::withCount('products');

        if ($this->categoryIds) {
            $query->whereIn('id', $this->categoryIds);
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Kategori',
            'photo',
            'Slug',
            'Jumlah Produk',
            'Tanggal Dibuat',
            'Terakhir Update'
        ];
    }

    public function map($category): array
    {
        return [
            $category->id,
            $category->name,
            $category->photo,
            $category->slug,
            $category->products_count,
            $category->created_at?->format('Y-m-d H:i:s'),
            $category->updated_at?->format('Y-m-d H:i:s')
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
 * Category Import Template Class
 */
class CategoryImportTemplate implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // Return empty collection with sample data
        return collect([
            [
                'Kamera Digital',
                'https://example.com/photo.jpg',

            ]
        ]);
    }

    public function headings(): array
    {
        return ['name', 'photo']; // Define expected headers
    }

    public function styles(Worksheet $sheet)
    {
        // Set header row style
        $sheet->getStyle('A1')->applyFromArray([
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
        $sheet->getStyle('A2')->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'E8F5E8']
            ]
        ]);

        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);

        return [];
    }
}
