<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Jobs\BulkCustomerUpdateJob;

class BulkActionProgressController extends Controller
{
    /**
     * Start a bulk action job
     */
    public function startBulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate,blacklist',
            'customer_ids' => 'required|array|min:1|max:1000',
            'customer_ids.*' => 'exists:customers,id'
        ]);

        // Generate unique job ID
        $jobId = Str::uuid()->toString();
        
        // For large operations (>100 records), use background job
        if (count($request->customer_ids) > 100) {
            // Dispatch job
            BulkCustomerUpdateJob::dispatch(
                $request->customer_ids,
                $request->action,
                $jobId,
                auth()->id()
            );
            
            return response()->json([
                'job_id' => $jobId,
                'message' => 'Bulk operation started in background',
                'use_polling' => true,
                'estimated_time' => $this->estimateTime(count($request->customer_ids), $request->action)
            ]);
        }
        
        // For smaller operations, process immediately
        return $this->processImmediately($request, $jobId);
    }

    /**
     * Get job progress
     */
    public function getProgress(Request $request)
    {
        $request->validate([
            'job_id' => 'required|string'
        ]);

        $progress = Cache::get("bulk_job_progress_{$request->job_id}");
        
        if (!$progress) {
            return response()->json([
                'error' => 'Job not found or expired'
            ], 404);
        }

        return response()->json($progress);
    }

    /**
     * Process smaller operations immediately
     */
    private function processImmediately(Request $request, string $jobId)
    {
        try {
            $startTime = microtime(true);
            
            // Use the existing optimized CustomerController logic for immediate processing
            $controller = new \App\Http\Controllers\CustomerController();
            
            // Mock the bulk action request
            $bulkRequest = new Request();
            $bulkRequest->merge([
                'action' => $request->action,
                'customer_ids' => $request->customer_ids
            ]);
            
            // This will use the optimized chunked processing we just implemented
            $result = $controller->bulkAction($bulkRequest);
            
            $executionTime = microtime(true) - $startTime;
            
            return response()->json([
                'job_id' => $jobId,
                'message' => 'Operation completed immediately',
                'success' => true,
                'execution_time' => round($executionTime, 2),
                'count' => count($request->customer_ids)
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'job_id' => $jobId,
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Estimate processing time based on operation and record count
     */
    private function estimateTime(int $recordCount, string $action): array
    {
        // Base time per record in seconds
        $timePerRecord = match($action) {
            'delete' => 0.1,    // Delete is slower due to cascading
            'activate' => 0.02,  
            'deactivate' => 0.02,
            'blacklist' => 0.02,
            default => 0.05
        };
        
        $estimatedSeconds = $recordCount * $timePerRecord;
        
        return [
            'seconds' => round($estimatedSeconds),
            'formatted' => $estimatedSeconds > 60 
                ? round($estimatedSeconds / 60, 1) . ' minutes'
                : round($estimatedSeconds) . ' seconds'
        ];
    }
}
