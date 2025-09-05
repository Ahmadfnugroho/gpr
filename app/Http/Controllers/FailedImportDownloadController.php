<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Services\CustomNotificationService;
use App\Exports\FailedImportExport;
use Maatwebsite\Excel\Facades\Excel;

class FailedImportDownloadController extends Controller
{
    /**
     * Download failed import data
     */
    public function download(Request $request)
    {
        $notification = CustomNotificationService::getCurrentNotification();
        
        if (!$notification || empty($notification['download_data'])) {
            return response()->json(['error' => 'No failed import data available'], 404);
        }

        try {
            $export = new FailedImportExport(
                $notification['download_data'], 
                $notification['import_type'] ?? 'product'
            );
            
            $filename = 'failed_import_' . ($notification['import_type'] ?? 'product') . '_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download($export, $filename);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to generate download: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Clear notification
     */
    public function clearNotification()
    {
        CustomNotificationService::clearNotification();
        return response()->json(['success' => true]);
    }
}
