<?php

namespace App\Imports;

use App\Models\Bundling;
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

class BundlingImporter implements 
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
        $bundlingData = $this->normalizeRowData($row);
        $validator = $this->validateRowData($bundlingData, $rowNumber);
        
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

        $bundlingId = !empty($bundlingData['id']) ? (int) $bundlingData['id'] : null;
        $existingBundling = null;

        if ($bundlingId) {
            $existingBundling = Bundling::find($bundlingId);
            if (!$existingBundling) {
                $this->incrementFailed();
                $errorMessage = "Bundling dengan ID {$bundlingId} tidak ditemukan";
                $this->addError("Baris {$rowNumber}: {$errorMessage}");
                $this->addFailedRow($row, $rowNumber, $errorMessage);
                return;
            }
        } else {
            $existingBundling = Bundling::whereRaw('LOWER(name) = ?', [strtolower($bundlingData['name'])])->first();
        }

        if ($existingBundling) {
            if ($this->updateExisting) {
                $this->updateBundling($existingBundling, $bundlingData, $rowNumber);
            } else {
                $this->incrementFailed();
                $errorMessage = "Bundling '{$bundlingData['name']}' sudah ada";
                $this->addError("Baris {$rowNumber}: {$errorMessage}");
                $this->addFailedRow($row, $rowNumber, $errorMessage);
                return;
            }
        } else {
            $this->createBundling($bundlingData, $rowNumber);
        }
    }

    /**
     * Normalize row data to consistent format
     */
    protected function normalizeRowData(array $row): array
    {
        return [
            'id' => !empty($row['id']) ? (int) $row['id'] : null,
            'name' => trim($row['name'] ?? ''),
            'description' => trim($row['description'] ?? ''),
            'price' => !empty($row['price']) ? (float) str_replace(['.', ','], '', $row['price']) : 0,
            'status' => strtolower(trim($row['status'] ?? 'active')),
        ];
    }

    /**
     * Validate row data
     */
    protected function validateRowData(array $data, int $rowNumber)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:active,inactive',
        ];

        $messages = [
            'name.required' => 'Nama bundling wajib diisi',
            'name.max' => 'Nama bundling maksimal 255 karakter',
            'price.numeric' => 'Harga harus berupa angka',
            'price.min' => 'Harga tidak boleh negatif',
            'status.in' => 'Status hanya boleh: active, inactive',
        ];

        return Validator::make($data, $rules, $messages);
    }

    /**
     * Create new bundling
     */
    protected function createBundling(array $data, int $rowNumber): void
    {
        try {
            unset($data['id']);

            $bundling = Bundling::create([
                'name' => $data['name'],
                'description' => $data['description'] ?: null,
                'price' => $data['price'] ?: 0,
                'status' => $data['status'] ?: 'active',
            ]);
            
            $this->incrementSuccess();
            $this->addMessage("Baris {$rowNumber}: Berhasil menambahkan bundling '{$bundling->name}'");
            Log::info("Bundling imported successfully", [
                'row' => $rowNumber,
                'bundling_id' => $bundling->id,
                'name' => $bundling->name,
                'price' => $bundling->price,
                'status' => $bundling->status
            ]);
        } catch (Exception $e) {
            $this->incrementFailed();
            $this->addError("Baris {$rowNumber}: Gagal menambahkan bundling - {$e->getMessage()}");
        }
    }

    /**
     * Update existing bundling
     */
    protected function updateBundling(Bundling $bundling, array $data, int $rowNumber): void
    {
        try {
            $bundling->update([
                'name' => $data['name'],
                'description' => $data['description'] ?: $bundling->description,
                'price' => $data['price'] ?: $bundling->price,
                'status' => $data['status'] ?: $bundling->status,
            ]);
            
            $this->incrementUpdated();
            $this->addMessage("Baris {$rowNumber}: Berhasil mengupdate bundling '{$bundling->name}'");
            Log::info("Bundling updated successfully", [
                'row' => $rowNumber,
                'bundling_id' => $bundling->id,
                'name' => $bundling->name,
                'price' => $bundling->price,
                'status' => $bundling->status
            ]);
        } catch (Exception $e) {
            $this->incrementFailed();
            $this->addError("Baris {$rowNumber}: Gagal mengupdate bundling - {$e->getMessage()}");
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
            'id',
            'name',
            'description',
            'price',
            'status'
        ];
    }
}
