<?php

namespace App\Imports;

use App\Models\Category;
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

class CategoryImporter implements 
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
        $categoryData = $this->normalizeRowData($row);
        
        // Validate the data
        $validator = $this->validateRowData($categoryData, $rowNumber);
        
        if ($validator->fails()) {
            $this->importResults['failed']++;
            foreach ($validator->errors()->all() as $error) {
                $this->importResults['errors'][] = "Baris {$rowNumber}: {$error}";
            }
            return;
        }

        // Check if category exists (by name since it should be unique)
        $existingCategory = Category::where('name', $categoryData['name'])->first();
        
        if ($existingCategory) {
            if ($this->updateExisting) {
                $this->updateCategory($existingCategory, $categoryData, $rowNumber);
            } else {
                $this->importResults['failed']++;
                $this->importResults['errors'][] = "Baris {$rowNumber}: Kategori '{$categoryData['name']}' sudah ada";
                return;
            }
        } else {
            $this->createCategory($categoryData, $rowNumber);
        }
    }

    /**
     * Normalize row data to consistent format
     */
    protected function normalizeRowData(array $row): array
    {
        return [
            'name' => trim($row['nama_kategori'] ?? $row['name'] ?? ''),
        ];
    }

    /**
     * Validate row data
     */
    protected function validateRowData(array $data, int $rowNumber)
    {
        return Validator::make($data, [
            'name' => 'required|string|max:255',
        ], [
            'name.required' => 'Nama kategori wajib diisi',
        ]);
    }

    /**
     * Create new category
     */
    protected function createCategory(array $data, int $rowNumber): void
    {
        // Create category
        $category = Category::create([
            'name' => $data['name'],
        ]);
        
        $this->importResults['success']++;
        Log::info("Category imported successfully", [
            'row' => $rowNumber,
            'category_id' => $category->id,
            'name' => $category->name
        ]);
    }

    /**
     * Update existing category
     */
    protected function updateCategory(Category $category, array $data, int $rowNumber): void
    {
        // Update category data
        $category->update([
            'name' => $data['name'],
        ]);
        
        $this->importResults['updated']++;
        Log::info("Category updated successfully", [
            'row' => $rowNumber,
            'category_id' => $category->id,
            'name' => $category->name
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
            'nama_kategori'
        ];
    }
}
