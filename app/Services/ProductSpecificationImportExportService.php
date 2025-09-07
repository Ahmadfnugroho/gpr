<?php

namespace App\Services;

use App\\Filament\\Imports\\ProductSpecificationImporter;
use App\Models\ProductSpecification;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductSpecificationImportExportService
{
    /**
     * Import product specifications from Excel file
     */
    public function importProductSpecifications(UploadedFile $file, bool $updateExisting = false): array
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
            $importer = new ProductSpecificationImporter($updateExisting);

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
     * Export product specifications to Excel
     */
    public function exportProductSpecifications(array $specificationIds = null): string
    {
        $export = new ProductSpecificationExport($specificationIds);
        $filename = 'product_specifications_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        Excel::store($export, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }

    /**
     * Generate Excel template for import
     */
    public function generateTemplate(): string
    {
        $template = new ProductSpecificationImportTemplate();
        $filename = 'product_specification_import_template.xlsx';
        
        Excel::store($template, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }
}

/**
 * ProductSpecification Export Class
 */
class ProductSpecificationExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $specificationIds;

    public function __construct(array $specificationIds = null)
    {
        $this->specificationIds = $specificationIds;
    }

    public function collection()
    {
        $query = ProductSpecification::with('product');
        
        if ($this->specificationIds) {
            $query->whereIn('id', $this->specificationIds);
        }
        
        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Product ID',
            'Product Name',
            'Specification Name',
            'Created At',
            'Updated At'
        ];
    }

    public function map($specification): array
    {
        return [
            $specification->id,
            $specification->product_id,
            $specification->product?->name ?? '',
            $specification->name,
            $specification->created_at?->format('Y-m-d H:i:s'),
            $specification->updated_at?->format('Y-m-d H:i:s')
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
 * ProductSpecification Import Template Class
 */
class ProductSpecificationImportTemplate implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // Get all products for reference
        $products = Product::select('id', 'name')->get();
        
        // Create sample data with product reference
        $sampleData = [
            [
                1, // product_id
                'High Resolution', // specification name
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
            'name',
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
