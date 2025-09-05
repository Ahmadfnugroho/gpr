<?php

namespace App\Imports;

use App\Models\RentalInclude;
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

class RentalIncludeImporter implements 
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
        $includeData = $this->normalizeRowData($row);
        
        // Validate the data
        $validator = $this->validateRowData($includeData, $rowNumber);
        
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

        // Check if both products exist
        $product = Product::find($includeData['product_id']);
        $includeProduct = Product::find($includeData['include_product_id']);
        
        if (!$product) {
            $this->incrementFailed();
            $errorMessage = "Product ID {$includeData['product_id']} tidak ditemukan";
            $this->addError("Baris {$rowNumber}: {$errorMessage}");
            $this->addFailedRow($row, $rowNumber, $errorMessage);
            return;
        }
        
        if (!$includeProduct) {
            $this->incrementFailed();
            $errorMessage = "Include Product ID {$includeData['include_product_id']} tidak ditemukan";
            $this->addError("Baris {$rowNumber}: {$errorMessage}");
            $this->addFailedRow($row, $rowNumber, $errorMessage);
            return;
        }

        // Check if rental include exists
        $existingInclude = RentalInclude::where('product_id', $includeData['product_id'])
            ->where('include_product_id', $includeData['include_product_id'])
            ->first();
        
        if ($existingInclude) {
            if ($this->updateExisting) {
                $this->updateRentalInclude($existingInclude, $includeData, $rowNumber);
            } else {
                $this->incrementFailed();
                $errorMessage = "Rental include '{$product->name}' -> '{$includeProduct->name}' sudah ada";
                $this->addError("Baris {$rowNumber}: {$errorMessage}");
                $this->addFailedRow($row, $rowNumber, $errorMessage);
                return;
            }
        } else {
            $this->createRentalInclude($includeData, $rowNumber);
        }
    }

    /**
     * Normalize row data to consistent format
     */
    protected function normalizeRowData(array $row): array
    {
        return [
            'product_id' => (int) ($row['product_id'] ?? 0),
            'include_product_id' => (int) ($row['include_product_id'] ?? 0),
            'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
        ];
    }

    /**
     * Validate row data
     */
    protected function validateRowData(array $data, int $rowNumber)
    {
        return Validator::make($data, [
            'product_id' => 'required|integer|exists:products,id',
            'include_product_id' => 'required|integer|exists:products,id|different:product_id',
            'quantity' => 'required|integer|min:1',
        ], [
            'product_id.required' => 'Product ID wajib diisi',
            'product_id.exists' => 'Product ID tidak ditemukan di database',
            'include_product_id.required' => 'Include Product ID wajib diisi',
            'include_product_id.exists' => 'Include Product ID tidak ditemukan di database',
            'include_product_id.different' => 'Include Product ID harus berbeda dengan Product ID',
            'quantity.required' => 'Quantity wajib diisi',
            'quantity.min' => 'Quantity minimal 1',
        ]);
    }

    /**
     * Create new rental include
     */
    protected function createRentalInclude(array $data, int $rowNumber): void
    {
        try {
            $rentalInclude = RentalInclude::create([
                'product_id' => $data['product_id'],
                'include_product_id' => $data['include_product_id'],
                'quantity' => $data['quantity'],
            ]);
            
            $this->incrementSuccess();
            $this->addMessage("Baris {$rowNumber}: Berhasil menambahkan rental include");
            Log::info("RentalInclude imported successfully", [
                'row' => $rowNumber,
                'rental_include_id' => $rentalInclude->id,
                'product_id' => $rentalInclude->product_id,
                'include_product_id' => $rentalInclude->include_product_id,
                'quantity' => $rentalInclude->quantity
            ]);
        } catch (Exception $e) {
            $this->incrementFailed();
            $this->addError("Baris {$rowNumber}: Gagal menambahkan rental include - {$e->getMessage()}");
        }
    }

    /**
     * Update existing rental include
     */
    protected function updateRentalInclude(RentalInclude $rentalInclude, array $data, int $rowNumber): void
    {
        try {
            $rentalInclude->update([
                'quantity' => $data['quantity'],
            ]);
            
            $this->incrementUpdated();
            $this->addMessage("Baris {$rowNumber}: Berhasil mengupdate rental include");
            Log::info("RentalInclude updated successfully", [
                'row' => $rowNumber,
                'rental_include_id' => $rentalInclude->id,
                'product_id' => $rentalInclude->product_id,
                'include_product_id' => $rentalInclude->include_product_id,
                'quantity' => $rentalInclude->quantity
            ]);
        } catch (Exception $e) {
            $this->incrementFailed();
            $this->addError("Baris {$rowNumber}: Gagal mengupdate rental include - {$e->getMessage()}");
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
            'include_product_id',
            'quantity'
        ];
    }
}
