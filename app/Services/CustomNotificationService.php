<?php

namespace App\Services;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Exports\FailedImportExport;
use Maatwebsite\Excel\Facades\Excel;

class CustomNotificationService
{
    /**
     * Show import results notification
     */
    public static function showImportResults(array $results, string $importType = 'product'): void
    {
        $notification = [
            'type' => !empty($results['errors']) ? 'warning' : 'success',
            'title' => !empty($results['errors']) ? 'Import Completed with Errors' : 'Import Successful',
            'message' => self::formatImportMessage($results),
            'errors' => $results['errors'] ?? [],
            'show_download' => !empty($results['failed_rows']),
            'download_data' => $results['failed_rows'] ?? [],
            'import_type' => $importType,
            'timestamp' => now()->timestamp
        ];

        Session::flash('import_notification', $notification);
    }

    /**
     * Format import message
     */
    private static function formatImportMessage(array $results): string
    {
        return sprintf(
            "Import selesai! Total: %d, Berhasil: %d, Diperbarui: %d, Gagal: %d",
            $results['total'] ?? 0,
            $results['success'] ?? 0,
            $results['updated'] ?? 0,
            $results['failed'] ?? 0
        );
    }

    /**
     * Generate failed import download
     */
    public static function generateFailedImportDownload(array $failedRows, string $importType): string
    {
        if (empty($failedRows)) {
            throw new \Exception('No failed rows to export');
        }

        $export = new FailedImportExport($failedRows, $importType);
        $filename = 'failed_import_' . $importType . '_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        Excel::store($export, $filename, 'public');
        
        return Storage::disk('public')->url($filename);
    }

    /**
     * Get current notification
     */
    public static function getCurrentNotification(): ?array
    {
        return Session::get('import_notification');
    }

    /**
     * Clear current notification
     */
    public static function clearNotification(): void
    {
        Session::forget('import_notification');
    }

    /**
     * Show custom success notification
     */
    public static function showSuccess(string $title, string $message): void
    {
        Session::flash('import_notification', [
            'type' => 'success',
            'title' => $title,
            'message' => $message,
            'errors' => [],
            'show_download' => false,
            'timestamp' => now()->timestamp
        ]);
    }

    /**
     * Show custom error notification
     */
    public static function showError(string $title, string $message): void
    {
        Session::flash('import_notification', [
            'type' => 'error',
            'title' => $title,
            'message' => $message,
            'errors' => [$message],
            'show_download' => false,
            'timestamp' => now()->timestamp
        ]);
    }

    /**
     * Show custom warning notification
     */
    public static function showWarning(string $title, string $message, array $errors = []): void
    {
        Session::flash('import_notification', [
            'type' => 'warning',
            'title' => $title,
            'message' => $message,
            'errors' => $errors,
            'show_download' => false,
            'timestamp' => now()->timestamp
        ]);
    }
}
