<?php

namespace App\Imports;

use App\Models\ProductSpecification;
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

class ProductSpecificationImporter implements 
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
        $specificationData = $this->normalizeRowData($row);
        
        // Validate the data
        $validator = $this->validateRowData($specificationData, $rowNumber);
        
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
        $product = Product::find($specificationData['product_id']);
        if (!$product) {
            $this->incrementFailed();
            $errorMessage = "Product ID {$specificationData['product_id']} tidak ditemukan";
            $this->addError("Baris {$rowNumber}: {$errorMessage}");
            $this->addFailedRow($row, $rowNumber, $errorMessage);
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
                $this->incrementFailed();
                $errorMessage = "Spesifikasi '{$specificationData['name']}' untuk produk '{$product->name}' sudah ada";
                $this->addError("Baris {$rowNumber}: {$errorMessage}");
                $this->addFailedRow($row, $rowNumber, $errorMessage);
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
        try {
            $specification = ProductSpecification::create([
                'product_id' => $data['product_id'],
                'name' => $data['name'],
            ]);
            
            $this->incrementSuccess();
            $this->addMessage("Baris {$rowNumber}: Berhasil menambahkan spesifikasi '{$data['name']}'");
            Log::info("ProductSpecification imported successfully", [
                'row' => $rowNumber,
                'specification_id' => $specification->id,
                'product_id' => $specification->product_id,
                'name' => $specification->name
            ]);
        } catch (Exception $e) {
            $this->incrementFailed();
            $this->addError("Baris {$rowNumber}: Gagal menambahkan spesifikasi - {$e->getMessage()}");
        }
    }

    /**
     * Update existing product specification
     */
    protected function updateProductSpecification(ProductSpecification $specification, array $data, int $rowNumber): void
    {
        try {
            $specification->update([
                'name' => $data['name'],
            ]);
            
            $this->incrementUpdated();
            $this->addMessage("Baris {$rowNumber}: Berhasil mengupdate spesifikasi '{$data['name']}'");
            Log::info("ProductSpecification updated successfully", [
                'row' => $rowNumber,
                'specification_id' => $specification->id,
                'product_id' => $specification->product_id,
                'name' => $specification->name
            ]);
        } catch (Exception $e) {
            $this->incrementFailed();
            $this->addError("Baris {$rowNumber}: Gagal mengupdate spesifikasi - {$e->getMessage()}");
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
            'name'
        ];
    }
}
