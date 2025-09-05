<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;
use Exception;

trait EnhancedImporterTrait
{
    protected $importResults = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'updated' => 0,
        'errors' => [],
        'failed_rows' => []
    ];

    protected $updateExisting = false;

    /**
     * Add failed row data for export with enhanced data structure
     */
    protected function addFailedRow(array $originalRow, int $rowNumber, string $errorMessage, array $mappedHeaders = []): void
    {
        // Create a flexible structure that can work with different data types
        $failedRowData = [
            'row_number' => $rowNumber,
            'error_message' => $errorMessage,
            'suggestions' => $this->generateSuggestions($errorMessage)
        ];

        // Add original data fields
        foreach ($originalRow as $key => $value) {
            $failedRowData[$key] = $value;
        }

        // Add mapped headers if provided
        foreach ($mappedHeaders as $originalKey => $mappedKey) {
            if (isset($originalRow[$mappedKey]) && !isset($failedRowData[$originalKey])) {
                $failedRowData[$originalKey] = $originalRow[$mappedKey];
            }
        }

        $this->importResults['failed_rows'][] = $failedRowData;
    }

    /**
     * Generate helpful suggestions based on error message
     */
    protected function generateSuggestions(string $errorMessage): string
    {
        $suggestions = [];

        // Common validation errors
        if (str_contains($errorMessage, 'wajib diisi') || str_contains($errorMessage, 'required')) {
            $suggestions[] = "Pastikan field yang wajib diisi tidak kosong";
        }

        if (str_contains($errorMessage, 'harus berupa angka') || str_contains($errorMessage, 'numeric')) {
            $suggestions[] = "Pastikan format angka sudah benar, tanpa simbol khusus";
        }

        if (str_contains($errorMessage, 'format email') || str_contains($errorMessage, 'email')) {
            $suggestions[] = "Pastikan format email sudah benar (contoh: user@domain.com)";
        }

        if (str_contains($errorMessage, 'sudah ada') || str_contains($errorMessage, 'duplicate')) {
            $suggestions[] = "Gunakan mode update untuk mengubah data yang sudah ada";
        }

        if (str_contains($errorMessage, 'tidak ditemukan') || str_contains($errorMessage, 'not found')) {
            $suggestions[] = "Pastikan referensi data sudah ada di sistem";
        }

        if (str_contains($errorMessage, 'maksimal') || str_contains($errorMessage, 'max')) {
            $suggestions[] = "Periksa panjang karakter sesuai batas maksimal";
        }

        if (str_contains($errorMessage, 'format tanggal') || str_contains($errorMessage, 'date')) {
            $suggestions[] = "Pastikan format tanggal sudah benar (YYYY-MM-DD)";
        }

        if (str_contains($errorMessage, 'serial number') || str_contains($errorMessage, 'Serial number')) {
            $suggestions[] = "Periksa format serial number dan pastikan tidak ada duplikasi";
        }

        return implode(' | ', $suggestions) ?: 'Periksa format data sesuai dengan template';
    }

    /**
     * Enhanced error logging with more context
     */
    protected function logImportError(string $message, int $rowNumber, array $rowData = [], string $level = 'error'): void
    {
        $context = [
            'row_number' => $rowNumber,
            'row_data' => $rowData,
            'importer_class' => get_class($this),
            'timestamp' => now()->toISOString()
        ];

        Log::{$level}("Import error: {$message}", $context);
    }

    /**
     * Enhanced success logging
     */
    protected function logImportSuccess(string $message, int $rowNumber, array $additionalData = []): void
    {
        $context = array_merge([
            'row_number' => $rowNumber,
            'importer_class' => get_class($this),
            'timestamp' => now()->toISOString()
        ], $additionalData);

        Log::info("Import success: {$message}", $context);
    }

    /**
     * Get import results
     */
    public function getImportResults(): array
    {
        return $this->importResults;
    }

    /**
     * Increment success counter
     */
    protected function incrementSuccess(): void
    {
        $this->importResults['success']++;
    }

    /**
     * Increment failed counter
     */
    protected function incrementFailed(): void
    {
        $this->importResults['failed']++;
    }

    /**
     * Increment updated counter
     */
    protected function incrementUpdated(): void
    {
        $this->importResults['updated']++;
    }

    /**
     * Increment total counter
     */
    protected function incrementTotal(): void
    {
        $this->importResults['total']++;
    }

    /**
     * Add error message
     */
    protected function addError(string $error): void
    {
        $this->importResults['errors'][] = $error;
    }

    /**
     * Reset import results
     */
    protected function resetImportResults(): void
    {
        $this->importResults = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'updated' => 0,
            'errors' => [],
            'failed_rows' => []
        ];
    }

    /**
     * Safe data getter with fallback
     */
    protected function safeGet(array $data, string $key, $default = '')
    {
        return isset($data[$key]) ? trim($data[$key]) : $default;
    }

    /**
     * Normalize boolean values with Indonesian support
     */
    protected function normalizeBoolean($value): bool
    {
        if (empty($value)) return false;
        
        $value = strtolower(trim($value));
        
        return in_array($value, ['ya', 'yes', 'true', '1', 'iya', 'benar']);
    }

    /**
     * Normalize numeric values
     */
    protected function normalizeNumeric($value): float
    {
        if (empty($value)) return 0;
        
        // Remove currency symbols and formatting
        $value = preg_replace('/[^0-9.,-]/', '', $value);
        $value = str_replace(',', '', $value);
        
        return floatval($value);
    }

    /**
     * Validate required fields
     */
    protected function validateRequiredFields(array $data, array $requiredFields, int $rowNumber): array
    {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = "Field '{$field}' wajib diisi";
            }
        }
        
        return $errors;
    }

    /**
     * Check if row should be skipped (empty or placeholder)
     */
    protected function shouldSkipRow(array $row): bool
    {
        // Skip if all values are empty
        $nonEmptyValues = array_filter($row, function($value) {
            return !empty(trim($value));
        });
        
        if (empty($nonEmptyValues)) {
            return true;
        }
        
        // Skip placeholder rows
        $firstValue = strtolower(trim(reset($row)));
        $placeholderPatterns = ['contoh', 'example', 'sample', 'dummy', 'test'];
        
        foreach ($placeholderPatterns as $pattern) {
            if (str_contains($firstValue, $pattern)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Format validation error messages
     */
    protected function formatValidationErrors(array $errors, int $rowNumber): string
    {
        return "Baris {$rowNumber}: " . implode(' | ', $errors);
    }
}
