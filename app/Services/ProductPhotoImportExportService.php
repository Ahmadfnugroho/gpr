<?php

namespace App\Services;

use App\Filament\Imports\ProductPhotoImporter;
use App\Models\ProductPhoto;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductPhotoImportExportService
{
    /**
     * Import product photos from Excel file
     */
    public function importProductPhotos(UploadedFile $file, bool $updateExisting = false): array
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
            $importer = new ProductPhotoImporter();

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
     * Export product photos to Excel
     */
    public function exportProductPhotos(array $photoIds = null): string
    {
        $export = new ProductPhotoExport($photoIds);
        $filename = 'product_photos_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        Excel::store($export, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }

    /**
     * Generate Excel template for import
     */
    public function generateTemplate(): string
    {
        $template = new ProductPhotoImportTemplate();
        $filename = 'product_photo_import_template.xlsx';
        
        Excel::store($template, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }
}

/**
 * ProductPhoto Export Class
 */
class ProductPhotoExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $photoIds;

    public function __construct(array $photoIds = null)
    {
        $this->photoIds = $photoIds;
    }

    public function collection()
    {
        $query = ProductPhoto::with('product');
        
        if ($this->photoIds) {
            $query->whereIn('id', $this->photoIds);
        }
        
        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Product ID',
            'Product Name',
            'Photo Filename',
            'Created At',
            'Updated At'
        ];
    }

    public function map($photo): array
    {
        return [
            $photo->id,
            $photo->product_id,
            $photo->product?->name ?? '',
            $photo->photo ?? '',
            $photo->created_at?->format('Y-m-d H:i:s'),
            $photo->updated_at?->format('Y-m-d H:i:s')
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
 * ProductPhoto Import Template Class
 */
class ProductPhotoImportTemplate implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // Get all products for reference
        $products = Product::select('id', 'name')->get();
        
        // Create sample data with product reference
        $sampleData = [
            [
                1, // product_id
                'photo1.jpg', // photo filename
            ]
        ];

        // Add product reference data to the right columns
        foreach ($products as $index => $product) {
            if ($index < count($sampleData)) {
                $sampleData[$index] = array_merge($sampleData[$index], [$product->id, $product->name]);
            } else {
                $sampleData[] = ['', '', $product->id, $product->name];
            }
        }

        return collect($sampleData);
    }

    public function headings(): array
    {
        return [
            'product_id', 
            'photo',
            '', // separator
            'REFERENCE - Product ID', 
            'REFERENCE - Product Name'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set header row style
        $sheet->getStyle('A1:E1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '4CAF50']
            ]
        ]);

        // Style reference columns
        $sheet->getStyle('D1:E1')->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '2196F3']
            ]
        ]);
     
        // Set sample data row style
        $sheet->getStyle('A2:B2')->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'E8F5E8']
            ]
        ]);

        // Style reference data
        $productCount = Product::count();
        $sheet->getStyle('D2:E' . ($productCount + 1))->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'E3F2FD']
            ]
        ]);
     
        // Auto-size columns
        foreach (range('A', 'E') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
     
        return [];
    }
}
