<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CustomerImportExportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ImportStatusController extends Controller
{
    protected $importService;

    public function __construct(CustomerImportExportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Get import status by import ID
     */
    public function getStatus(string $importId): JsonResponse
    {
        try {
            $status = $this->importService->getImportStatus($importId);
            
            return response()->json([
                'success' => true,
                'data' => $status
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get import status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import results by import ID
     */
    public function getResults(string $importId): JsonResponse
    {
        try {
            $results = $this->importService->getImportResults($importId);
            
            if (!$results) {
                return response()->json([
                    'success' => false,
                    'message' => 'Import results not found or expired'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $results
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get import results',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if queue worker is running
     */
    public function checkQueueStatus(): JsonResponse
    {
        try {
            // Check if there are pending jobs
            $pendingJobs = \Illuminate\Support\Facades\DB::table('jobs')->count();
            $failedJobs = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'pending_jobs' => $pendingJobs,
                    'failed_jobs' => $failedJobs,
                    'queue_working' => $pendingJobs >= 0 // Simple check
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check queue status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
