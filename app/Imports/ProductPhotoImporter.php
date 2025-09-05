<?php

namespace App\Imports;

use App\Models\ProductPhoto;
use App\Models\Product;
use App\Traits\EnhancedImporterTrait;
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
    use Importable, SkipsErrors, SkipsFailures, EnhancedImporterTrait;

    // importResults and updateExisting moved to EnhancedImporterTrait

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
            $this->incrementTotal();
            $rowNumber = $index + 2; // +2 because index starts from 0 and there's a header
            $rowArray = $row->toArray();

            // Skip empty rows
            if ($this->shouldSkipRow($rowArray)) {
                continue;
            }

            try {
                $this->processRow($rowArray, $rowNumber);
            } catch (Exception $e) {
                $this->incrementFailed();
                $errorMessage = $e->getMessage();
                $this->addError("Baris {$rowNumber}: {$errorMessage}");
                $this->addFailedRow($rowArray, $rowNumber, $errorMessage);
                $this->logImportError($errorMessage, $rowNumber, $rowArray);
            }
        }
    }

    /**
     * Process individual row
     */
    protected function processRow(array $row, int $rowNumber): void
    {
        // Normalize and validate row data
        $photoData = $this->normalizeRowData($row);
        
        // Validate the data
        $validator = $this->validateRowData($photoData, $rowNumber);
        
        if ($validator->fails()) {
            $this->incrementFailed();
            $errorMessages = [];
            foreach ($validator->errors()->all() as $error) {
                $this->addError("Baris {$rowNumber}: {$error}");
                $errorMessages[] = $error;
            }
            $this->addFailedRow($row, $rowNumber, implode(' | ', $errorMessages));
            return;
        }

        // Check if product exists
        $product = Product::find($photoData['product_id']);
        if (!$product) {
            $this->incrementFailed();
            $errorMessage = "Product ID {$photoData['product_id']} tidak ditemukan";
            $this->addError("Baris {$rowNumber}: {$errorMessage}");
            $this->addFailedRow($row, $rowNumber, $errorMessage);
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
                $this->incrementFailed();
                $errorMessage = "Photo '{$photoData['photo']}' untuk produk '{$product->name}' sudah ada";
                $this->addError("Baris {$rowNumber}: {$errorMessage}");
                $this->addFailedRow($row, $rowNumber, $errorMessage);
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
        try {
            $photo = ProductPhoto::create([
                'product_id' => $data['product_id'],
                'photo' => $data['photo'],
            ]);
            
            $this->incrementSuccess();
            $this->addMessage("Baris {$rowNumber}: Berhasil menambahkan foto produk '{$data['photo']}'");
            Log::info("ProductPhoto imported successfully", [
                'row' => $rowNumber,
                'photo_id' => $photo->id,
                'product_id' => $photo->product_id,
                'photo' => $photo->photo
            ]);
        } catch (Exception $e) {
            $this->incrementFailed();
            $this->addError("Baris {$rowNumber}: Gagal menambahkan foto produk - {$e->getMessage()}");
        }
    }

    /**
     * Update existing product photo
     */
    protected function updateProductPhoto(ProductPhoto $photo, array $data, int $rowNumber): void
    {
        try {
            // For photos, we typically don't need to update much since filename is the key
            // But we can update the photo filename if needed
            $photo->update([
                'photo' => $data['photo'],
            ]);
            
            $this->incrementUpdated();
            $this->addMessage("Baris {$rowNumber}: Berhasil mengupdate foto produk '{$data['photo']}'");
            Log::info("ProductPhoto updated successfully", [
                'row' => $rowNumber,
                'photo_id' => $photo->id,
                'product_id' => $photo->product_id,
                'photo' => $photo->photo
            ]);
        } catch (Exception $e) {
            $this->incrementFailed();
            $this->addError("Baris {$rowNumber}: Gagal mengupdate foto produk - {$e->getMessage()}");
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
            'product_id',
            'photo'
        ];
    }
}
