<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Brand;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;
use Exception;

class ProductImporter implements 
    ToCollection, 
    WithHeadingRow, 
    WithBatchInserts,
    WithChunkReading,
    SkipsOnError,
    SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    protected $importResults = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'updated' => 0,
        'errors' => []
    ];

    protected $updateExisting = false;

    public function __construct($updateExisting = false)
    {
        $this->updateExisting = $updateExisting;
    }

    /**
     * Process the collection of imported data
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $this->importResults['total']++;
            $rowNumber = $index + 2; // +2 because index starts from 0 and there's a header

            try {
                $this->processRow($row->toArray(), $rowNumber);
            } catch (Exception $e) {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$rowNumber}: {$e->getMessage()}";
                Log::error("Import error on row {$rowNumber}: " . $e->getMessage(), [
                    'row_data' => $row->toArray()
                ]);
            }
        }
    }

    /**
     * Process individual row
     */
    protected function processRow(array $row, int $rowNumber): void
    {
        // Normalize and validate row data
        $productData = $this->normalizeRowData($row);
        
        // Validate the data
        $validator = $this->validateRowData($productData, $rowNumber);
        
        if ($validator->fails()) {
            $this->importResults['failed']++;
            foreach ($validator->errors()->all() as $error) {
                $this->importResults['errors'][] = "Baris {$rowNumber}: {$error}";
            }
            return;
        }

        // Check if product exists (by name since it should be unique)
        $existingProduct = Product::where('name', $productData['name'])->first();
        
        if ($existingProduct) {
            if ($this->updateExisting) {
                $this->updateProduct($existingProduct, $productData, $rowNumber);
            } else {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$rowNumber}: Produk '{$productData['name']}' sudah ada";
                return;
            }
        } else {
            $this->createProduct($productData, $rowNumber);
        }
    }

    /**
     * Normalize row data to consistent format
     */
    protected function normalizeRowData(array $row): array
    {
        return [
            'name' => trim($row['nama_produk'] ?? $row['name'] ?? ''),
            'price' => $this->normalizePrice($row['harga'] ?? $row['price'] ?? ''),
            'thumbnail' => trim($row['thumbnail'] ?? $row['foto'] ?? ''),
            'status' => $this->normalizeStatus($row['status'] ?? ''),
            'category' => trim($row['kategori'] ?? $row['category'] ?? ''),
            'brand' => trim($row['brand'] ?? ''),
            'sub_category' => trim($row['sub_kategori'] ?? $row['sub_category'] ?? ''),
            'premiere' => $this->normalizeBoolean($row['premiere'] ?? ''),
            'serial_numbers' => $this->normalizeSerialNumbers($row['serial_numbers'] ?? $row['nomor_seri'] ?? ''),
        ];
    }

    /**
     * Validate row data
     */
    protected function validateRowData(array $data, int $rowNumber)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'thumbnail' => 'nullable|string|max:500',
            'status' => 'required|in:available,unavailable,maintenance',
            'category' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'sub_category' => 'nullable|string|max:255',
            'premiere' => 'boolean',
            'serial_numbers' => 'nullable|array',
            'serial_numbers.*' => 'string|max:100|distinct',
        ], [
            'name.required' => 'Nama produk wajib diisi',
            'price.required' => 'Harga wajib diisi',
            'price.numeric' => 'Harga harus berupa angka',
            'price.min' => 'Harga tidak boleh kurang dari 0',
            'thumbnail.max' => 'URL thumbnail maksimal 500 karakter',
            'status.required' => 'Status wajib diisi',
            'status.in' => 'Status harus: available, unavailable, atau maintenance',
            'serial_numbers.array' => 'Serial numbers harus berupa array',
            'serial_numbers.*.string' => 'Setiap serial number harus berupa string',
            'serial_numbers.*.max' => 'Serial number maksimal 100 karakter',
            'serial_numbers.*.distinct' => 'Serial number tidak boleh duplikat',
        ]);
    }

    /**
     * Create new product
     */
    protected function createProduct(array $data, int $rowNumber): void
    {
        // Get or create related models
        $categoryId = $this->getOrCreateCategory($data['category']);
        $brandId = $this->getOrCreateBrand($data['brand']);
        $subCategoryId = $this->getOrCreateSubCategory($data['sub_category'], $categoryId);
        
        // Prepare product data
        $productData = [
            'name' => $data['name'],
            'price' => $data['price'],
            'status' => $data['status'],
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'sub_category_id' => $subCategoryId,
            'premiere' => $data['premiere'],
        ];
        
        // Add thumbnail if provided
        if (!empty($data['thumbnail'])) {
            $productData['thumbnail'] = $data['thumbnail'];
        }
        
        // Create product
        $product = Product::create($productData);
        
        // Create serial numbers (ProductItems) if provided
        $this->createProductItems($product, $data['serial_numbers'], $rowNumber);
        
        $this->importResults['success']++;
        Log::info("Product imported successfully", [
            'row' => $rowNumber,
            'product_id' => $product->id,
            'name' => $product->name,
            'serial_numbers_count' => count($data['serial_numbers'])
        ]);
    }

    /**
     * Update existing product
     */
    protected function updateProduct(Product $product, array $data, int $rowNumber): void
    {
        // Get or create related models
        $categoryId = $this->getOrCreateCategory($data['category']);
        $brandId = $this->getOrCreateBrand($data['brand']);
        $subCategoryId = $this->getOrCreateSubCategory($data['sub_category'], $categoryId);
        
        // Prepare update data
        $updateData = [
            'name' => $data['name'],
            'price' => $data['price'],
            'status' => $data['status'],
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'sub_category_id' => $subCategoryId,
            'premiere' => $data['premiere'],
        ];
        
        // Add thumbnail if provided
        if (!empty($data['thumbnail'])) {
            $updateData['thumbnail'] = $data['thumbnail'];
        }
        
        // Update product data
        $product->update($updateData);
        
        // Update serial numbers - remove existing ones and create new ones
        if (!empty($data['serial_numbers'])) {
            // Delete existing product items
            $product->items()->delete();
            
            // Create new product items
            $this->createProductItems($product, $data['serial_numbers'], $rowNumber);
        }
        
        $this->importResults['updated']++;
        Log::info("Product updated successfully", [
            'row' => $rowNumber,
            'product_id' => $product->id,
            'name' => $product->name,
            'serial_numbers_count' => count($data['serial_numbers'])
        ]);
    }

    /**
     * Get or create category
     */
    protected function getOrCreateCategory(?string $categoryName): ?int
    {
        if (empty($categoryName)) return null;
        
        $category = Category::firstOrCreate(
            ['name' => $categoryName],
            ['name' => $categoryName]
        );
        
        return $category->id;
    }

    /**
     * Get or create brand
     */
    protected function getOrCreateBrand(?string $brandName): ?int
    {
        if (empty($brandName)) return null;
        
        $brand = Brand::firstOrCreate(
            ['name' => $brandName],
            ['name' => $brandName]
        );
        
        return $brand->id;
    }

    /**
     * Get or create sub category
     */
    protected function getOrCreateSubCategory(?string $subCategoryName, ?int $categoryId): ?int
    {
        if (empty($subCategoryName)) return null;
        
        $subCategory = SubCategory::firstOrCreate(
            ['name' => $subCategoryName, 'category_id' => $categoryId],
            ['name' => $subCategoryName, 'category_id' => $categoryId]
        );
        
        return $subCategory->id;
    }

    /**
     * Normalize price value
     */
    protected function normalizePrice($price): float
    {
        if (empty($price)) return 0;
        
        // Remove currency symbols and formatting
        $price = preg_replace('/[^0-9.,]/', '', $price);
        $price = str_replace(',', '', $price);
        
        return floatval($price);
    }

    /**
     * Normalize status value
     */
    protected function normalizeStatus(?string $status): string
    {
        if (empty($status)) return 'available';
        
        $status = strtolower(trim($status));
        
        if (in_array($status, ['tersedia', 'available', 'ada', 'a'])) {
            return 'available';
        } elseif (in_array($status, ['tidak tersedia', 'unavailable', 'tidak ada', 'u'])) {
            return 'unavailable';
        } elseif (in_array($status, ['maintenance', 'perbaikan', 'service', 'm'])) {
            return 'maintenance';
        }
        
        return 'available'; // default
    }

    /**
     * Normalize boolean value
     */
    protected function normalizeBoolean($value): bool
    {
        if (empty($value)) return false;
        
        $value = strtolower(trim($value));
        
        return in_array($value, ['ya', 'yes', 'true', '1', 'iya']);
    }

    /**
     * Normalize serial numbers from string to array
     */
    protected function normalizeSerialNumbers($serialNumbers): array
    {
        if (empty($serialNumbers)) return [];
        
        // Handle different separators: comma, semicolon, pipe, newline
        $separators = [',', ';', '|', "\n", "\r\n"];
        
        foreach ($separators as $separator) {
            if (strpos($serialNumbers, $separator) !== false) {
                $numbers = explode($separator, $serialNumbers);
                break;
            }
        }
        
        // If no separator found, treat as single serial number
        if (!isset($numbers)) {
            $numbers = [$serialNumbers];
        }
        
        // Clean and filter serial numbers
        return array_filter(array_map('trim', $numbers), function ($item) {
            return !empty($item);
        });
    }

    /**
     * Create ProductItems (serial numbers) for a product
     */
    protected function createProductItems(Product $product, array $serialNumbers, int $rowNumber): void
    {
        if (empty($serialNumbers)) {
            return;
        }

        foreach ($serialNumbers as $serialNumber) {
            try {
                // Check if serial number already exists
                $existingItem = ProductItem::where('serial_number', $serialNumber)->first();
                
                if ($existingItem) {
                    $this->importResults['errors'][] = "Baris {$rowNumber}: Serial number '{$serialNumber}' sudah ada pada produk '{$existingItem->product->name}'";
                    continue;
                }

                ProductItem::create([
                    'product_id' => $product->id,
                    'serial_number' => $serialNumber,
                    'is_available' => true,
                ]);

                Log::info("ProductItem created successfully", [
                    'product_id' => $product->id,
                    'serial_number' => $serialNumber,
                    'row' => $rowNumber
                ]);
            } catch (Exception $e) {
                $this->importResults['errors'][] = "Baris {$rowNumber}: Gagal membuat serial number '{$serialNumber}': {$e->getMessage()}";
                Log::error("Failed to create ProductItem", [
                    'product_id' => $product->id,
                    'serial_number' => $serialNumber,
                    'error' => $e->getMessage(),
                    'row' => $rowNumber
                ]);
            }
        }
    }

    /**
     * Get import results
     */
    public function getImportResults(): array
    {
        return $this->importResults;
    }

    /**
     * Batch insert size
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * Chunk reading size
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Get expected headers for template
     */
    public static function getExpectedHeaders(): array
    {
        return [
            'nama_produk',
            'harga',
            'thumbnail',
            'status',
            'kategori',
            'brand',
            'sub_kategori',
            'premiere',
            'serial_numbers'
        ];
    }
}
