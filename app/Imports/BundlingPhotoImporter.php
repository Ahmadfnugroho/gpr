<?php

namespace App\Imports;

use App\Models\BundlingPhoto;
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

class BundlingPhotoImporter implements 
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
        if (empty($row['bundling_id']) && empty($row['photo'])) {
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

        // Check if bundling exists
        $bundling = Bundling::find($photoData['bundling_id']);
        
        if (!$bundling) {
            $this->importResults['failed']++;
            $this->importResults['errors'][] = "Baris {$rowNumber}: Bundling ID {$photoData['bundling_id']} tidak ditemukan";
            return;
        }

        // Check if bundling photo exists (duplicate check based on bundling_id and photo)
        $existingPhoto = BundlingPhoto::where('bundling_id', $photoData['bundling_id'])
            ->where('photo', $photoData['photo'])
            ->first();
        
        if ($existingPhoto) {
            if ($this->updateExisting) {
                // For bundling photos, there's no additional data to update since we only have bundling_id and photo
                // So we'll just log that the photo already exists
                $this->importResults['updated']++;
                Log::info("BundlingPhoto already exists", [
                    'row' => $rowNumber,
                    'bundling_photo_id' => $existingPhoto->id,
                    'bundling_id' => $existingPhoto->bundling_id,
                    'photo' => $existingPhoto->photo
                ]);
            } else {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$rowNumber}: Photo '{$photoData['photo']}' untuk bundling '{$bundling->name}' sudah ada";
                return;
            }
        } else {
            $this->createBundlingPhoto($photoData, $rowNumber);
        }
    }

    /**
     * Normalize row data to consistent format
     */
    protected function normalizeRowData(array $row): array
    {
        return [
            'bundling_id' => (int) ($row['bundling_id'] ?? 0),
            'photo' => trim($row['photo'] ?? ''),
        ];
    }

    /**
     * Validate row data
     */
    protected function validateRowData(array $data, int $rowNumber)
    {
        return Validator::make($data, [
            'bundling_id' => 'required|integer|exists:bundlings,id',
            'photo' => 'required|string|max:255',
        ], [
            'bundling_id.required' => 'Bundling ID wajib diisi',
            'bundling_id.exists' => 'Bundling ID tidak ditemukan di database',
            'photo.required' => 'Photo wajib diisi',
            'photo.max' => 'Photo maksimal 255 karakter',
        ]);
    }

    /**
     * Create new bundling photo
     */
    protected function createBundlingPhoto(array $data, int $rowNumber): void
    {
        $bundlingPhoto = BundlingPhoto::create([
            'bundling_id' => $data['bundling_id'],
            'photo' => $data['photo'],
        ]);
        
        $this->importResults['success']++;
        Log::info("BundlingPhoto imported successfully", [
            'row' => $rowNumber,
            'bundling_photo_id' => $bundlingPhoto->id,
            'bundling_id' => $bundlingPhoto->bundling_id,
            'photo' => $bundlingPhoto->photo
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
            'bundling_id',
            'photo'
        ];
    }
}
