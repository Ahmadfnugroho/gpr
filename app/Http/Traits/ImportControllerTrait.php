<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait ImportControllerTrait
{
    /**
     * Standard file validation rules for import
     */
    protected function getImportValidationRules(): array
    {
        return [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240', // 10MB max
            'update_existing' => 'sometimes|boolean'
        ];
    }

    /**
     * Standard file validation messages for import
     */
    protected function getImportValidationMessages(): array
    {
        return [
            'file.required' => 'File import wajib dipilih',
            'file.mimes' => 'File harus berformat Excel (.xlsx, .xls) atau CSV',
            'file.max' => 'Ukuran file maksimal 10MB'
        ];
    }

    /**
     * Handle import preview with standard response
     */
    protected function handleImportPreview(Request $request, $importService, string $importType = 'product')
    {
        $request->validate(
            $this->getImportValidationRules(),
            $this->getImportValidationMessages()
        );

        try {
            $preview = $importService->previewImport($request->file('file'));
            
            return response()->json([
                'success' => true,
                'data' => $preview
            ]);

        } catch (\Exception $e) {
            Log::error("Import preview error for {$importType}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat preview: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle file validation with standard response
     */
    protected function handleFileValidation(Request $request, $importService, string $importType = 'product')
    {
        $request->validate(
            ['file' => 'required|file|mimes:xlsx,xls,csv|max:10240'],
            $this->getImportValidationMessages()
        );

        try {
            $validation = $importService->validateFileStructure($request->file('file'));
            
            return response()->json([
                'success' => true,
                'validation' => $validation
            ]);

        } catch (\Exception $e) {
            Log::error("File validation error for {$importType}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal memvalidasi file: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle import execution with standard response
     */
    protected function handleImportExecution(Request $request, $importService, string $importType = 'product', string $sessionKey = 'failed_import_rows')
    {
        $request->validate(
            $this->getImportValidationRules(),
            $this->getImportValidationMessages()
        );

        try {
            $updateExisting = $request->boolean('update_existing', false);
            $methodName = 'import' . ucfirst($importType) . 's';
            
            if (!method_exists($importService, $methodName)) {
                $methodName = 'importData'; // fallback method name
            }
            
            $results = $importService->{$methodName}($request->file('file'), $updateExisting);
            
            // Store failed rows in session if any
            if (!empty($results['failed_rows'])) {
                session([$sessionKey => $results['failed_rows']]);
                session(['import_type' => $importType]);
            }

            return response()->json([
                'success' => true,
                'results' => $results,
                'has_failed_rows' => !empty($results['failed_rows'])
            ]);

        } catch (\Exception $e) {
            Log::error("Import execution error for {$importType}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan import: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle template download with standard response
     */
    protected function handleTemplateDownload($importService, string $importType = 'product', string $filename = null)
    {
        try {
            $filePath = $importService->generateTemplate();
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Template file could not be generated'
                ], 500);
            }

            $downloadName = $filename ?: "template_import_{$importType}.xlsx";
            return response()->download($filePath, $downloadName)->deleteFileAfterSend();

        } catch (\Exception $e) {
            Log::error("Template download error for {$importType}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle failed rows download with standard response
     */
    protected function handleFailedRowsDownload($importService, string $sessionKey = 'failed_import_rows')
    {
        try {
            // Get failed rows from session
            $failedRows = session($sessionKey);
            $importType = session('import_type', 'data');
            
            if (empty($failedRows)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data gagal untuk diunduh'
                ], 400);
            }

            $filePath = $importService->exportFailedImport($failedRows, $importType);
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'File gagal import tidak dapat dibuat'
                ], 500);
            }

            // Clear failed rows from session after download
            session()->forget([$sessionKey, 'import_type']);

            $filename = "data_gagal_import_{$importType}_" . date('Y-m-d_H-i-s') . '.xlsx';
            return response()->download($filePath, $filename)->deleteFileAfterSend();

        } catch (\Exception $e) {
            Log::error('Failed rows download error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunduh data gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle session clearing with standard response
     */
    protected function handleSessionClear(array $sessionKeys = ['import_results', 'failed_import_rows', 'import_type'])
    {
        session()->forget($sessionKeys);
        
        return response()->json([
            'success' => true,
            'message' => 'Session data cleared'
        ]);
    }

    /**
     * Standard success response format
     */
    protected function successResponse($data = null, string $message = 'Success')
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    /**
     * Standard error response format
     */
    protected function errorResponse(string $message, int $code = 500, $data = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}
