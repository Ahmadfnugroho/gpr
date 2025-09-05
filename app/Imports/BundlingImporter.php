<?php

namespace App\Imports;

use App\Models\Bundling;
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
        // Skip rows that don't have required data
        if (empty($row['name']) || trim($row['name']) === '') {
            $this->importResults['total']--; // Don't count empty rows
            return;
        }

        // Normalize and validate row data
        $bundlingData = $this->normalizeRowData($row);
        
        // Validate the data
        $validator = $this->validateRowData($bundlingData, $rowNumber);
        
        if ($validator->fails()) {
            $this->importResults['failed']++;
            foreach ($validator->errors()->all() as $error) {
                $this->importResults['errors'][] = "Baris {$rowNumber}: {$error}";
            }
            return;
        }

        // Determine if this is an update or create operation
        $bundlingId = !empty($bundlingData['id']) ? (int) $bundlingData['id'] : null;
        $existingBundling = null;

        if ($bundlingId) {
            // Look for existing by ID
            $existingBundling = Bundling::find($bundlingId);
            if (!$existingBundling) {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$rowNumber}: Bundling dengan ID {$bundlingId} tidak ditemukan";
                return;
            }
        } else {
            // Look for existing by name (case insensitive)
            $existingBundling = Bundling::whereRaw('LOWER(name) = ?', [strtolower($bundlingData['name'])])->first();
        }

        if ($existingBundling) {
            if ($this->updateExisting) {
                $this->updateBundling($existingBundling, $bundlingData, $rowNumber);
            } else {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$rowNumber}: Bundling '{$bundlingData['name']}' sudah ada";
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
        // Remove id from data for creation
        unset($data['id']);

        $bundling = Bundling::create([
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
            'price' => $data['price'] ?: 0,
            'status' => $data['status'] ?: 'active',
        ]);
        
        $this->importResults['success']++;
        Log::info("Bundling imported successfully", [
            'row' => $rowNumber,
            'bundling_id' => $bundling->id,
            'name' => $bundling->name,
            'price' => $bundling->price,
            'status' => $bundling->status
        ]);
    }

    /**
     * Update existing bundling
     */
    protected function updateBundling(Bundling $bundling, array $data, int $rowNumber): void
    {
        $bundling->update([
            'name' => $data['name'],
            'description' => $data['description'] ?: $bundling->description,
            'price' => $data['price'] ?: $bundling->price,
            'status' => $data['status'] ?: $bundling->status,
        ]);
        
        $this->importResults['updated']++;
        Log::info("Bundling updated successfully", [
            'row' => $rowNumber,
            'bundling_id' => $bundling->id,
            'name' => $bundling->name,
            'price' => $bundling->price,
            'status' => $bundling->status
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
            'id',
            'name',
            'description',
            'price',
            'status'
        ];
    }
}
