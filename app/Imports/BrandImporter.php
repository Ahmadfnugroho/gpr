<?php

namespace App\Imports;

use App\Models\Brand;
use App\Traits\EnhancedImporterTrait;
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
    use Importable, SkipsErrors, SkipsFailures, EnhancedImporterTrait;

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
            $rowNumber = $index + 2;
            $rowArray = $row->toArray();

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
        $brandData = $this->normalizeRowData($row);
        
        // Validate the data
        $validator = $this->validateRowData($brandData, $rowNumber);
        
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

        // Check if brand exists (by name since it should be unique)
        $existingBrand = Brand::where('name', $brandData['name'])->first();
        
        if ($existingBrand) {
            if ($this->updateExisting) {
                $this->updateBrand($existingBrand, $brandData, $rowNumber);
            } else {
                $this->incrementFailed();
                $errorMessage = "Brand '{$brandData['name']}' sudah ada";
                $this->addError("Baris {$rowNumber}: {$errorMessage}");
                $this->addFailedRow($row, $rowNumber, $errorMessage);
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
            'logo' => trim($row['logo'] ?? ''),
        ];
    }

    /**
     * Validate row data
     */
    protected function validateRowData(array $data, int $rowNumber)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Nama brand wajib diisi',
            'logo.string' => 'Logo harus berupa text/URL',
        ]);
    }

    /**
     * Create new brand
     */
    protected function createBrand(array $data, int $rowNumber): void
    {
        try {
            $brand = Brand::create([
                'name' => $data['name'],
                'logo' => $data['logo'] ?? null,
            ]);
            
            $this->incrementSuccess();
            $this->addMessage("Baris {$rowNumber}: Berhasil menambahkan brand '{$brand->name}'");
            Log::info("Brand imported successfully", [
                'row' => $rowNumber,
                'brand_id' => $brand->id,
                'name' => $brand->name
            ]);
        } catch (Exception $e) {
            $this->incrementFailed();
            $this->addError("Baris {$rowNumber}: Gagal menambahkan brand - {$e->getMessage()}");
        }
    }

    /**
     * Update existing brand
     */
    protected function updateBrand(Brand $brand, array $data, int $rowNumber): void
    {
        try {
            $brand->update([
                'name' => $data['name'],
                'logo' => $data['logo'] ?? null,
            ]);
            
            $this->incrementUpdated();
            $this->addMessage("Baris {$rowNumber}: Berhasil mengupdate brand '{$brand->name}'");
            Log::info("Brand updated successfully", [
                'row' => $rowNumber,
                'brand_id' => $brand->id,
                'name' => $brand->name
            ]);
        } catch (Exception $e) {
            $this->incrementFailed();
            $this->addError("Baris {$rowNumber}: Gagal mengupdate brand - {$e->getMessage()}");
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
            'nama_brand',
            'logo'
        ];
    }
}
