<?php

namespace App\Services;

use App\\Filament\\Imports\\RentalIncludeImporter;
use App\Models\RentalInclude;
use App\Models\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RentalIncludeImportExportService
{
    /**
     * Import rental includes from Excel file
     */
    public function importRentalIncludes(UploadedFile $file, bool $updateExisting = false): array
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
            $importer = new RentalIncludeImporter($updateExisting);

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
     * Export rental includes to Excel
     */
    public function exportRentalIncludes(array $includeIds = null): string
    {
        $export = new RentalIncludeExport($includeIds);
        $filename = 'rental_includes_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        Excel::store($export, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }

    /**
     * Generate Excel template for import
     */
    public function generateTemplate(): string
    {
        $template = new RentalIncludeImportTemplate();
        $filename = 'rental_include_import_template.xlsx';
        
        Excel::store($template, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }
}

/**
 * RentalInclude Export Class
 */
class RentalIncludeExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $includeIds;

    public function __construct(array $includeIds = null)
    {
        $this->includeIds = $includeIds;
    }

    public function collection()
    {
        $query = RentalInclude::with(['product', 'includeProduct']);
        
        if ($this->includeIds) {
            $query->whereIn('id', $this->includeIds);
        }
        
        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Product ID',
            'Product Name',
            'Include Product ID',
            'Include Product Name',
            'Quantity',
            'Created At',
            'Updated At'
        ];
    }

    public function map($rentalInclude): array
    {
        return [
            $rentalInclude->id,
            $rentalInclude->product_id,
            $rentalInclude->product?->name ?? '',
            $rentalInclude->include_product_id,
            $rentalInclude->includeProduct?->name ?? '',
            $rentalInclude->quantity,
            $rentalInclude->created_at?->format('Y-m-d H:i:s'),
            $rentalInclude->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
            
            // Set auto width for all columns
            'A:H' => ['alignment' => ['wrapText' => true]]
        ];
    }
}

/**
 * RentalInclude Import Template Class
 */
class RentalIncludeImportTemplate implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // Get all products for reference
        $products = Product::select('id', 'name')->get();
        
        // Create sample data with product reference
        $sampleData = [
            [
                1, // product_id
                2, // include_product_id
                5, // quantity
            ]
        ];

        // Add product reference data to the right columns
        foreach ($products as $index => $product) {
            if ($index < count($sampleData)) {
                $sampleData[$index] = array_merge($sampleData[$index], [$product->id, $product->name]);
            } else {
                $sampleData[] = ['', '', '', $product->id, $product->name];
            }
        }

        return collect($sampleData);
    }

    public function headings(): array
    {
        return [
            'product_id', 
            'include_product_id',
            'quantity',
            '', // separator
            'REFERENCE - Product ID', 
            'REFERENCE - Product Name'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Set header row style
        $sheet->getStyle('A1:F1')->applyFromArray([
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
        $sheet->getStyle('E1:F1')->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => '2196F3']
            ]
        ]);
     
        // Set sample data row style
        $sheet->getStyle('A2:C2')->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'E8F5E8']
            ]
        ]);

        // Style reference data
        $productCount = Product::count();
        $sheet->getStyle('E2:F' . ($productCount + 1))->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'E3F2FD']
            ]
        ]);
     
        // Auto-size columns
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
     
        return [];
    }
}
