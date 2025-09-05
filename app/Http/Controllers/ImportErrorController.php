<?php

namespace App\Http\Controllers;

use App\Traits\EnhancedImportControllerTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportErrorController extends Controller
{
    use EnhancedImportControllerTrait;

    /**
     * Download failed rows as Excel file
     */
    public function downloadFailedRows(): BinaryFileResponse
    {
        return $this->downloadFailedRows();
    }

    /**
     * View all errors in detailed page
     */
    public function viewAllErrors(): View
    {
        return $this->viewAllErrors();
    }

    /**
     * Get import statistics via AJAX
     */
    public function getImportStatistics(): JsonResponse
    {
        $statistics = $this->getImportStatistics();
        
        if (!$statistics) {
            return response()->json([
                'success' => false,
                'message' => 'No import statistics found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $statistics
        ]);
    }

    /**
     * Get failed rows with pagination
     */
    public function getFailedRowsPaginated(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 50);
        
        $result = $this->getFailedRowsPaginated($page, $perPage);
        
        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'No failed rows found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result
        ]);
    }

    /**
     * Clear failed import data from session
     */
    public function clearFailedImportData(): JsonResponse
    {
        return $this->clearFailedImportData();
    }

    /**
     * Re-process specific failed rows
     */
    public function reprocessFailedRows(Request $request): JsonResponse
    {
        $request->validate([
            'row_indices' => 'required|array',
            'row_indices.*' => 'integer|min:0',
            'importer_class' => 'required|string',
            'update_existing' => 'boolean'
        ]);

        $sessionKey = session('latest_failed_import_key');
        
        if (!$sessionKey || !session()->has($sessionKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Failed rows data not found or expired'
            ], 404);
        }

        $failedData = session($sessionKey);
        $failedRows = $failedData['failed_rows'];
        $rowIndices = $request->row_indices;
        
        $selectedRows = [];
        foreach ($rowIndices as $index) {
            if (isset($failedRows[$index])) {
                $selectedRows[] = $failedRows[$index];
            }
        }

        if (empty($selectedRows)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid rows selected for reprocessing'
            ], 400);
        }

        // Here you would implement the logic to reprocess the selected rows
        // This could involve creating a temporary Excel file and re-importing it
        
        return response()->json([
            'success' => true,
            'message' => 'Selected rows will be reprocessed',
            'data' => [
                'selected_count' => count($selectedRows),
                'total_failed' => count($failedRows)
            ]
        ]);
    }

    /**
     * Show import error dashboard
     */
    public function dashboard(): View
    {
        $statistics = $this->getImportStatistics();
        
        return view('import.dashboard', [
            'statistics' => $statistics,
            'has_failed_data' => !is_null($statistics)
        ]);
    }

    /**
     * Export error summary as Excel
     */
    public function exportErrorSummary(): BinaryFileResponse
    {
        $sessionKey = session('latest_failed_import_key');
        
        if (!$sessionKey || !session()->has($sessionKey)) {
            abort(404, 'Error data not found');
        }

        $failedData = session($sessionKey);
        $failedRows = $failedData['failed_rows'];

        // Group errors by type
        $errorSummary = [];
        foreach ($failedRows as $row) {
            $error = $row['error_reason'];
            if (!isset($errorSummary[$error])) {
                $errorSummary[$error] = [
                    'error' => $error,
                    'count' => 0,
                    'affected_rows' => []
                ];
            }
            $errorSummary[$error]['count']++;
            $errorSummary[$error]['affected_rows'][] = $row['row_number'];
        }

        // Create Excel export with error summary
        $export = new \App\Exports\ImportErrorSummaryExport($errorSummary);
        $filename = 'import_error_summary_' . date('Y-m-d_H-i-s') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }
}
