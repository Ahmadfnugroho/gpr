<?php

namespace App\Imports;

use App\Models\Brand;
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

class BrandImporter implements 
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
        $brandData = $this->normalizeRowData($row);
        
        // Validate the data
        $validator = $this->validateRowData($brandData, $rowNumber);
        
        if ($validator->fails()) {
            $this->importResults['failed']++;
            foreach ($validator->errors()->all() as $error) {
                $this->importResults['errors'][] = "Baris {$rowNumber}: {$error}";
            }
            return;
        }

        // Check if brand exists (by name since it should be unique)
        $existingBrand = Brand::where('name', $brandData['name'])->first();
        
        if ($existingBrand) {
            if ($this->updateExisting) {
                $this->updateBrand($existingBrand, $brandData, $rowNumber);
            } else {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$rowNumber}: Brand '{$brandData['name']}' sudah ada";
                return;
            }
        } else {
            $this->createBrand($brandData, $rowNumber);
        }
    }

    /**
     * Normalize row data to consistent format
     */
    protected function normalizeRowData(array $row): array
    {
        return [
            'name' => trim($row['nama_brand'] ?? $row['name'] ?? ''),
            'premiere' => $this->normalizeBoolean($row['premiere'] ?? ''),
        ];
    }

    /**
     * Validate row data
     */
    protected function validateRowData(array $data, int $rowNumber)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'premiere' => 'boolean',
        ], [
            'name.required' => 'Nama brand wajib diisi',
        ]);
    }

    /**
     * Create new brand
     */
    protected function createBrand(array $data, int $rowNumber): void
    {
        // Create brand
        $brand = Brand::create([
            'name' => $data['name'],
            'premiere' => $data['premiere'],
        ]);
        
        $this->importResults['success']++;
        Log::info("Brand imported successfully", [
            'row' => $rowNumber,
            'brand_id' => $brand->id,
            'name' => $brand->name
        ]);
    }

    /**
     * Update existing brand
     */
    protected function updateBrand(Brand $brand, array $data, int $rowNumber): void
    {
        // Update brand data
        $brand->update([
            'name' => $data['name'],
            'premiere' => $data['premiere'],
        ]);
        
        $this->importResults['updated']++;
        Log::info("Brand updated successfully", [
            'row' => $rowNumber,
            'brand_id' => $brand->id,
            'name' => $brand->name
        ]);
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
            'nama_brand',
            'premiere'
        ];
    }
}
