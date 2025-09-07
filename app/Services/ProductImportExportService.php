<?php

namespace App\Services;

use App\Filament\Imports\ProductImporter;
use App\Exports\FailedImportExport;
use App\Models\Product;
use App\Models\Brand;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductImportExportService
{
    /**
     * Import products from Excel file
     */
    public function importProducts(UploadedFile $file, bool $updateExisting = false): array
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
            $importer = new ProductImporter($updateExisting);

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
     * Export products to Excel
     */
    public function exportProducts(array $productIds = null): string
    {
        $export = new ProductExport($productIds);
        $filename = 'products_export_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        Excel::store($export, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }

    /**
     * Generate Excel template for import
     */
    public function generateTemplate(): string
    {
        $template = new ProductImportTemplate();
        $filename = 'product_import_template.xlsx';
        
        Excel::store($template, $filename, 'public');
        
        return Storage::disk('public')->path($filename);
    }

    /**
     * Export failed import rows to Excel
     */
    public function exportFailedImport(array $failedRows, string $importType = 'product'): string
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
    public function previewImport(UploadedFile $file, int $maxRows = 10): array
    {
        try {
            $data = Excel::toArray(new ProductImporter(), $file);
            
            if (empty($data) || empty($data[0])) {
                return [
                    'success' => false,
                    'error' => 'File is empty or corrupted'
                ];
            }

            $headers = array_keys($data[0][0] ?? []);
            $rows = array_slice($data[0], 0, $maxRows);
            
            // Validate structure
            $validation = $this->validateFileStructure($file);
            
            return [
                'success' => true,
                'headers' => $headers,
                'sample_rows' => $rows,
                'total_rows' => count($data[0]),
                'validation' => $validation,
                'expected_headers' => ProductImporter::getExpectedHeaders()
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
    public function validateFileStructure(UploadedFile $file): array
    {
        try {
            $data = Excel::toArray(new ProductImporter(), $file);
            
            if (empty($data) || empty($data[0])) {
                return [
                    'valid' => false,
                    'errors' => ['File is empty or corrupted']
                ];
            }

            $headers = array_keys($data[0][0] ?? []);
            $expectedHeaders = ProductImporter::getExpectedHeaders();
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
 * Product Export Class
 */
class ProductExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $productIds;

    public function __construct(array $productIds = null)
    {
        $this->productIds = $productIds;
    }

    public function collection()
    {
        $query = Product::with(['category', 'brand', 'subCategory']);
        
        if ($this->productIds) {
            $query->whereIn('id', $this->productIds);
        }
        
        return $query->get();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Produk',
            'Harga',
            'Status',
            'Kategori',
            'Brand',
            'Sub Kategori',
            'Premiere',
            'Thumbnail',
            'Slug',
            'Tanggal Dibuat',
            'Terakhir Update'
        ];
    }

    public function map($product): array
    {
        return [
            $product->id,
            $product->name,
            $product->price,
            $product->status,
            $product->category->name ?? '',
            $product->brand->name ?? '',
            $product->subCategory->name ?? '',
            $product->premiere ? 'Ya' : 'Tidak',
            $product->thumbnail ?? '',
            $product->slug,
            $product->created_at?->format('Y-m-d H:i:s'),
            $product->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
            
            // Set auto width for all columns
            'A:L' => ['alignment' => ['wrapText' => true]]
        ];
    }
}

/**
 * Product Import Template Class
 */
class ProductImportTemplate implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        // Return empty collection with sample data
        return collect([
            [
                'Kamera DSLR Canon',
                '5000000',
                'available',
                'Camera',
                'Canon',
                'DSLR',
                'Ya',
            ]
        ]);
    }

    public function headings(): array
    {
        return ProductImporter::getExpectedHeaders();
    }

    public function styles(Worksheet $sheet)
    {
        // Set header row style
        $sheet->getStyle('A1:G1')->applyFromArray([
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
        $sheet->getStyle('A2:G2')->applyFromArray([
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'E8F5E8']
            ]
        ]);
     
        // Auto-size columns
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
     
        // Add data validation for status column (C)
        $validation = $sheet->getCell('C2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Input error');
        $validation->setError('Value is not in list.');
        $validation->setPromptTitle('Pick from list');
        $validation->setPrompt('Please pick a value from the drop-down list.');
        $validation->setFormula1('"available,unavailable,maintenance"');
     
        // Add data validation for premiere column (G)
        $premiereValidation = $sheet->getCell('G2')->getDataValidation();
        $premiereValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $premiereValidation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $premiereValidation->setAllowBlank(true);
        $premiereValidation->setShowInputMessage(true);
        $premiereValidation->setShowErrorMessage(true);
        $premiereValidation->setShowDropDown(true);
        $premiereValidation->setErrorTitle('Input error');
        $premiereValidation->setError('Value is not in list.');
        $premiereValidation->setPromptTitle('Pick from list');
        $premiereValidation->setPrompt('Please pick a value from the drop-down list.');
        $premiereValidation->setFormula1('"Ya,Tidak"');
     
        return [];
    }
}
