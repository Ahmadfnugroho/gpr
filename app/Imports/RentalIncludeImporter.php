<?php

namespace App\Imports;

use App\Models\RentalInclude;
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

class RentalIncludeImporter implements 
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
        if (empty($row['product_id']) && empty($row['include_product_id'])) {
            $this->importResults['total']--; // Don't count empty rows
            return;
        }

        // Normalize and validate row data
        $includeData = $this->normalizeRowData($row);
        
        // Validate the data
        $validator = $this->validateRowData($includeData, $rowNumber);
        
        if ($validator->fails()) {
            $this->importResults['failed']++;
            foreach ($validator->errors()->all() as $error) {
                $this->importResults['errors'][] = "Baris {$rowNumber}: {$error}";
            }
            return;
        }

        // Check if both products exist
        $product = Product::find($includeData['product_id']);
        $includeProduct = Product::find($includeData['include_product_id']);
        
        if (!$product) {
            $this->importResults['failed']++;
            $this->importResults['errors'][] = "Baris {$rowNumber}: Product ID {$includeData['product_id']} tidak ditemukan";
            return;
        }
        
        if (!$includeProduct) {
            $this->importResults['failed']++;
            $this->importResults['errors'][] = "Baris {$rowNumber}: Include Product ID {$includeData['include_product_id']} tidak ditemukan";
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
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$rowNumber}: Rental include '{$product->name}' -> '{$includeProduct->name}' sudah ada";
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
        $rentalInclude = RentalInclude::create([
            'product_id' => $data['product_id'],
            'include_product_id' => $data['include_product_id'],
            'quantity' => $data['quantity'],
        ]);
        
        $this->importResults['success']++;
        Log::info("RentalInclude imported successfully", [
            'row' => $rowNumber,
            'rental_include_id' => $rentalInclude->id,
            'product_id' => $rentalInclude->product_id,
            'include_product_id' => $rentalInclude->include_product_id,
            'quantity' => $rentalInclude->quantity
        ]);
    }

    /**
     * Update existing rental include
     */
    protected function updateRentalInclude(RentalInclude $rentalInclude, array $data, int $rowNumber): void
    {
        $rentalInclude->update([
            'quantity' => $data['quantity'],
        ]);
        
        $this->importResults['updated']++;
        Log::info("RentalInclude updated successfully", [
            'row' => $rowNumber,
            'rental_include_id' => $rentalInclude->id,
            'product_id' => $rentalInclude->product_id,
            'include_product_id' => $rentalInclude->include_product_id,
            'quantity' => $rentalInclude->quantity
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
            'include_product_id',
            'quantity'
        ];
    }
}
