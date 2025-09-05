<?php

namespace App\Imports;

use App\Models\ProductPhoto;
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

class ProductPhotoImporter implements 
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
        if (empty($row['product_id']) && empty($row['photo'])) {
            $this->importResults['total']--; // Don't count empty rows
            return;
        }

        // Normalize and validate row data
        $photoData = $this->normalizeRowData($row);
        
        // Validate the data
        $validator = $this->validateRowData($photoData, $rowNumber);
        
        if ($validator->fails()) {
            $this->importResults['failed']++;
            foreach ($validator->errors()->all() as $error) {
                $this->importResults['errors'][] = "Baris {$rowNumber}: {$error}";
            }
            return;
        }

        // Check if product exists
        $product = Product::find($photoData['product_id']);
        if (!$product) {
            $this->importResults['failed']++;
            $this->importResults['errors'][] = "Baris {$rowNumber}: Product ID {$photoData['product_id']} tidak ditemukan";
            return;
        }

        // Check if photo exists for this product
        $existingPhoto = ProductPhoto::where('product_id', $photoData['product_id'])
            ->where('photo', $photoData['photo'])
            ->first();
        
        if ($existingPhoto) {
            if ($this->updateExisting) {
                $this->updateProductPhoto($existingPhoto, $photoData, $rowNumber);
            } else {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$rowNumber}: Photo '{$photoData['photo']}' untuk produk '{$product->name}' sudah ada";
                return;
            }
        } else {
            $this->createProductPhoto($photoData, $rowNumber);
        }
    }

    /**
     * Normalize row data to consistent format
     */
    protected function normalizeRowData(array $row): array
    {
        return [
            'product_id' => (int) ($row['product_id'] ?? 0),
            'photo' => trim($row['photo'] ?? ''),
        ];
    }

    /**
     * Validate row data
     */
    protected function validateRowData(array $data, int $rowNumber)
    {
        return Validator::make($data, [
            'product_id' => 'required|integer|exists:products,id',
            'photo' => 'required|string|max:255',
        ], [
            'product_id.required' => 'Product ID wajib diisi',
            'product_id.exists' => 'Product ID tidak ditemukan di database',
            'photo.required' => 'Nama file foto wajib diisi',
        ]);
    }

    /**
     * Create new product photo
     */
    protected function createProductPhoto(array $data, int $rowNumber): void
    {
        $photo = ProductPhoto::create([
            'product_id' => $data['product_id'],
            'photo' => $data['photo'],
        ]);
        
        $this->importResults['success']++;
        Log::info("ProductPhoto imported successfully", [
            'row' => $rowNumber,
            'photo_id' => $photo->id,
            'product_id' => $photo->product_id,
            'photo' => $photo->photo
        ]);
    }

    /**
     * Update existing product photo
     */
    protected function updateProductPhoto(ProductPhoto $photo, array $data, int $rowNumber): void
    {
        // For photos, we typically don't need to update much since filename is the key
        // But we can update the photo filename if needed
        $photo->update([
            'photo' => $data['photo'],
        ]);
        
        $this->importResults['updated']++;
        Log::info("ProductPhoto updated successfully", [
            'row' => $rowNumber,
            'photo_id' => $photo->id,
            'product_id' => $photo->product_id,
            'photo' => $photo->photo
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
            'photo'
        ];
    }
}
