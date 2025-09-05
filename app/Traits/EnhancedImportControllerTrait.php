<?php

namespace App\Traits;

use App\Exports\FailedImportExport;
use App\Services\UniversalImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

trait EnhancedImportControllerTrait
{
    /**
     * Process import with enhanced error handling and failed rows tracking
     */
    public function processEnhancedImport(Request $request, string $importerClass, bool $updateExisting = false)
    {
        try {
            $file = $request->file('file');
            
            if (!$file) {
                return $this->sendErrorResponse('File tidak ditemukan');
            }

            // Create service instance
            $service = new UniversalImportService($importerClass);
            
            // Validate file structure first
            $validation = $service->validateFile($file);
            if (!$validation['valid']) {
                return $this->sendErrorResponse($validation['errors'][0] ?? 'File tidak valid');
            }

            // Process import
            $results = $service->import($file, $updateExisting);
            
            // Store failed rows in session for download
            if (!empty($results['failed_rows'])) {
                $this->storeFailedRowsInSession($results['failed_rows'], $importerClass);
            }

            // Create detailed notification
            $this->createImportNotification($results, $importerClass);

            return $this->sendSuccessResponse($results);

        } catch (\Exception $e) {
            \Log::error('Import error: ' . $e->getMessage(), [
                'file' => $file ? $file->getClientOriginalName() : 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return $this->sendErrorResponse('Import gagal: ' . $e->getMessage());
        }
    }

    /**
     * Store failed rows in session for later download
     */
    protected function storeFailedRowsInSession(array $failedRows, string $importerClass): void
    {
        $sessionKey = 'failed_import_' . Str::slug(class_basename($importerClass)) . '_' . time();
        
        session([
            $sessionKey => [
                'failed_rows' => $failedRows,
                'importer_class' => $importerClass,
                'timestamp' => now()->toDateTimeString(),
                'total_failed' => count($failedRows)
            ]
        ]);

        // Also store the session key for easy retrieval
        session(['latest_failed_import_key' => $sessionKey]);
    }

    /**
     * Create detailed import notification with download option
     */
    protected function createImportNotification(array $results, string $importerClass): void
    {
        $total = $results['total'] ?? 0;
        $success = $results['success'] ?? 0;
        $updated = $results['updated'] ?? 0;
        $failed = $results['failed'] ?? 0;
        
        $title = $this->getImportTitle($success, $updated, $failed);
        $body = $this->getImportBody($results);
        
        // Determine notification type
        $notificationType = $this->getNotificationType($success, $failed, $total);
        
        // Create notification
        $notification = Notification::make()
            ->title($title)
            ->body($body)
            ->status($notificationType)
            ->persistent() // Keep notification visible
            ->duration(null); // Don't auto-hide

        // Add download action if there are failed rows
        if ($failed > 0 && !empty($results['failed_rows'])) {
            $notification->actions([
                \Filament\Notifications\Actions\Action::make('download_failed')
                    ->label('Download Failed Rows')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('danger')
                    ->url(route('import.download-failed'))
                    ->openUrlInNewTab(),
                    
                \Filament\Notifications\Actions\Action::make('view_errors')
                    ->label('Lihat Semua Error')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning')
                    ->action('viewAllErrors')
            ]);
        }

        $notification->send();
    }

    /**
     * Get import notification title
     */
    protected function getImportTitle(int $success, int $updated, int $failed): string
    {
        if ($failed === 0) {
            return '✅ Import Berhasil Sempurna';
        } elseif ($success + $updated > 0) {
            return '⚠️ Import Selesai dengan Error';
        } else {
            return '❌ Import Gagal';
        }
    }

    /**
     * Get import notification body
     */
    protected function getImportBody(array $results): string
    {
        $total = $results['total'] ?? 0;
        $success = $results['success'] ?? 0;
        $updated = $results['updated'] ?? 0;
        $failed = $results['failed'] ?? 0;
        
        $body = "Total: {$total}, Berhasil: {$success}, Diupdate: {$updated}, Gagal: {$failed}";
        
        // Add preview of errors if any
        if ($failed > 0 && !empty($results['errors'])) {
            $errorPreview = array_slice($results['errors'], 0, 3);
            $body .= "\n\nContoh Error:\n" . implode("\n", $errorPreview);
            
            if (count($results['errors']) > 3) {
                $remainingErrors = count($results['errors']) - 3;
                $body .= "\n... dan {$remainingErrors} error lainnya";
            }
        }

        return $body;
    }

    /**
     * Get notification type based on results
     */
    protected function getNotificationType(int $success, int $failed, int $total): string
    {
        if ($failed === 0) {
            return 'success';
        } elseif ($success > 0 || ($failed / $total) < 0.5) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    /**
     * Download failed rows as Excel file
     */
    public function downloadFailedRows()
    {
        $sessionKey = session('latest_failed_import_key');
        
        if (!$sessionKey || !session()->has($sessionKey)) {
            return redirect()->back()->with('error', 'Data failed rows tidak ditemukan atau sudah expired');
        }

        $failedData = session($sessionKey);
        $failedRows = $failedData['failed_rows'];
        $importerClass = $failedData['importer_class'];

        if (empty($failedRows)) {
            return redirect()->back()->with('error', 'Tidak ada baris yang gagal untuk didownload');
        }

        // Get expected headers from importer class
        $expectedHeaders = $importerClass::getExpectedHeaders();
        
        // Create export
        $export = new FailedImportExport($failedRows, $expectedHeaders);
        
        $filename = 'failed_import_' . Str::slug(class_basename($importerClass)) . '_' . date('Y-m-d_H-i-s') . '.xlsx';

        // Clear the session data after download
        session()->forget([$sessionKey, 'latest_failed_import_key']);

        return Excel::download($export, $filename);
    }

    /**
     * Show all errors in a detailed view
     */
    public function viewAllErrors()
    {
        $sessionKey = session('latest_failed_import_key');
        
        if (!$sessionKey || !session()->has($sessionKey)) {
            return redirect()->back()->with('error', 'Data error tidak ditemukan atau sudah expired');
        }

        $failedData = session($sessionKey);
        
        return view('import.errors', [
            'failed_rows' => $failedData['failed_rows'],
            'importer_class' => class_basename($failedData['importer_class']),
            'timestamp' => $failedData['timestamp'],
            'total_failed' => $failedData['total_failed'],
            'download_url' => route('import.download-failed')
        ]);
    }

    /**
     * Send success response
     */
    protected function sendSuccessResponse(array $results)
    {
        return response()->json([
            'success' => true,
            'message' => 'Import completed successfully',
            'data' => $results
        ]);
    }

    /**
     * Send error response
     */
    protected function sendErrorResponse(string $message)
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 400);
    }

    /**
     * Get import statistics for display
     */
    public function getImportStatistics()
    {
        $sessionKey = session('latest_failed_import_key');
        
        if (!$sessionKey || !session()->has($sessionKey)) {
            return null;
        }

        return session($sessionKey);
    }

    /**
     * Clear failed import data from session
     */
    public function clearFailedImportData()
    {
        $sessionKey = session('latest_failed_import_key');
        
        if ($sessionKey) {
            session()->forget([$sessionKey, 'latest_failed_import_key']);
        }

        return response()->json(['success' => true, 'message' => 'Data cleared successfully']);
    }

    /**
     * Get failed rows with pagination for large datasets
     */
    public function getFailedRowsPaginated(int $page = 1, int $perPage = 50)
    {
        $sessionKey = session('latest_failed_import_key');
        
        if (!$sessionKey || !session()->has($sessionKey)) {
            return null;
        }

        $failedData = session($sessionKey);
        $failedRows = $failedData['failed_rows'];
        
        $total = count($failedRows);
        $offset = ($page - 1) * $perPage;
        $paginatedRows = array_slice($failedRows, $offset, $perPage);
        
        return [
            'rows' => $paginatedRows,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => ceil($total / $perPage),
                'has_more' => $offset + $perPage < $total
            ]
        ];
    }
}
