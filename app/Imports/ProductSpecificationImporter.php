<?php

namespace App\Imports;

use App\Models\ProductSpecification;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Carbon\Carbon;
use Exception;

class ProductSpecificationImporter implements 
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
        // Skip rows that are empty or only reference data
        if (empty($row['product_id']) && empty($row['name'])) {
            $this->importResults['total']--; // Don't count empty rows
            return;
        }

        // Normalize and validate row data
        $specificationData = $this->normalizeRowData($row);
        
        // Validate the data
        $validator = $this->validateRowData($specificationData, $rowNumber);
        
        if ($validator->fails()) {
            $this->importResults['failed']++;
            foreach ($validator->errors()->all() as $error) {
                $this->importResults['errors'][] = "Baris {$rowNumber}: {$error}";
            }
            return;
        }

        // Check if product exists
        $product = Product::find($specificationData['product_id']);
        if (!$product) {
            $this->importResults['failed']++;
            $this->importResults['errors'][] = "Baris {$rowNumber}: Product ID {$specificationData['product_id']} tidak ditemukan";
            return;
        }

        // Check if specification exists for this product
        $existingSpecification = ProductSpecification::where('product_id', $specificationData['product_id'])
            ->where('name', $specificationData['name'])
            ->first();
        
        if ($existingSpecification) {
            if ($this->updateExisting) {
                $this->updateProductSpecification($existingSpecification, $specificationData, $rowNumber);
            } else {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$rowNumber}: Spesifikasi '{$specificationData['name']}' untuk produk '{$product->name}' sudah ada";
                return;
            }
        } else {
            $this->createProductSpecification($specificationData, $rowNumber);
        }
    }

    /**
     * Normalize row data to consistent format
     */
    protected function normalizeRowData(array $row): array
    {
        return [
            'product_id' => (int) ($row['product_id'] ?? 0),
            'name' => trim($row['name'] ?? ''),
        ];
    }

    /**
     * Validate row data
     */
    protected function validateRowData(array $data, int $rowNumber)
    {
        return Validator::make($data, [
            'product_id' => 'required|integer|exists:products,id',
            'name' => 'required|string|max:255',
        ], [
            'product_id.required' => 'Product ID wajib diisi',
            'product_id.exists' => 'Product ID tidak ditemukan di database',
            'name.required' => 'Nama spesifikasi wajib diisi',
        ]);
    }

    /**
     * Create new product specification
     */
    protected function createProductSpecification(array $data, int $rowNumber): void
    {
        $specification = ProductSpecification::create([
            'product_id' => $data['product_id'],
            'name' => $data['name'],
        ]);
        
        $this->importResults['success']++;
        Log::info("ProductSpecification imported successfully", [
            'row' => $rowNumber,
            'specification_id' => $specification->id,
            'product_id' => $specification->product_id,
            'name' => $specification->name
        ]);
    }

    /**
     * Update existing product specification
     */
    protected function updateProductSpecification(ProductSpecification $specification, array $data, int $rowNumber): void
    {
        $specification->update([
            'name' => $data['name'],
        ]);
        
        $this->importResults['updated']++;
        Log::info("ProductSpecification updated successfully", [
            'row' => $rowNumber,
            'specification_id' => $specification->id,
            'product_id' => $specification->product_id,
            'name' => $specification->name
        ]);
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
            'product_id',
            'name'
        ];
    }
}
