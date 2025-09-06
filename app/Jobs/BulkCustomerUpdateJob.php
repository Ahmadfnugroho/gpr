<?php

namespace App\Jobs;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BulkCustomerUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes timeout
    public $tries = 3;
    public $maxExceptions = 3;

    protected $customerIds;
    protected $action;
    protected $jobId;
    protected $userId;

    public function __construct(array $customerIds, string $action, string $jobId, ?int $userId = null)
    {
        $this->customerIds = $customerIds;
        $this->action = $action;
        $this->jobId = $jobId;
        $this->userId = $userId;
        
        // Set queue priority based on operation type
        $this->onQueue($action === 'delete' ? 'high' : 'default');
    }

    public function handle()
    {
        $startTime = microtime(true);
        $totalRecords = count($this->customerIds);
        
        // Initialize progress tracking
        $this->updateProgress(0, $totalRecords, 'Starting bulk operation...');
        
        try {
            DB::beginTransaction();
            
            $count = 0;
            $chunks = array_chunk($this->customerIds, 50); // Smaller chunks for jobs
            $totalChunks = count($chunks);
            
            foreach ($chunks as $index => $chunk) {
                $chunkResult = $this->processChunk($chunk);
                $count += $chunkResult;
                
                // Update progress
                $processed = ($index + 1) * 50;
                $processed = min($processed, $totalRecords);
                $this->updateProgress($processed, $totalRecords, "Processed {$processed} of {$totalRecords} records");
                
                // Prevent memory leaks
                if ($index % 10 === 0) {
                    gc_collect_cycles();
                }
            }
            
            DB::commit();
            
            $executionTime = microtime(true) - $startTime;
            
            // Mark as completed
            $this->updateProgress($totalRecords, $totalRecords, 'Completed', [
                'success_count' => $count,
                'execution_time' => round($executionTime, 2),
                'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB'
            ]);
            
            Log::info('Bulk customer job completed', [
                'job_id' => $this->jobId,
                'action' => $this->action,
                'success_count' => $count,
                'execution_time' => round($executionTime, 2) . 's',
                'memory_peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->updateProgress(0, $totalRecords, 'Failed: ' . $e->getMessage(), [
                'error' => true,
                'error_message' => $e->getMessage()
            ]);
            
            Log::error('Bulk customer job failed', [
                'job_id' => $this->jobId,
                'action' => $this->action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    private function processChunk(array $chunk): int
    {
        switch ($this->action) {
            case 'delete':
                return $this->deleteChunk($chunk);
            case 'activate':
                return $this->updateStatusChunk($chunk, Customer::STATUS_ACTIVE);
            case 'deactivate':
                return $this->updateStatusChunk($chunk, Customer::STATUS_INACTIVE);
            case 'blacklist':
                return $this->updateStatusChunk($chunk, Customer::STATUS_BLACKLIST);
            default:
                return 0;
        }
    }

    private function deleteChunk(array $chunk): int
    {
        // Batch delete related records first
        DB::table('customer_phone_numbers')
          ->whereIn('customer_id', $chunk)
          ->delete();
        
        DB::table('customer_photos')
          ->whereIn('customer_id', $chunk)
          ->delete();
        
        // Then delete customers
        return Customer::whereIn('id', $chunk)->delete();
    }

    private function updateStatusChunk(array $chunk, string $status): int
    {
        return Customer::whereIn('id', $chunk)
                     ->update([
                         'status' => $status,
                         'updated_at' => now()
                     ]);
    }

    private function updateProgress(int $processed, int $total, string $message, array $additional = [])
    {
        $progress = [
            'job_id' => $this->jobId,
            'processed' => $processed,
            'total' => $total,
            'percentage' => $total > 0 ? round(($processed / $total) * 100, 2) : 0,
            'message' => $message,
            'timestamp' => now()->toISOString(),
            'status' => $processed >= $total ? 'completed' : 'processing'
        ];
        
        $progress = array_merge($progress, $additional);
        
        // Store progress in cache for 1 hour
        Cache::put("bulk_job_progress_{$this->jobId}", $progress, 3600);
    }

    public function failed(\Throwable $exception)
    {
        $this->updateProgress(0, count($this->customerIds), 'Failed: ' . $exception->getMessage(), [
            'error' => true,
            'error_message' => $exception->getMessage(),
            'status' => 'failed'
        ]);
        
        Log::error('Bulk customer job failed permanently', [
            'job_id' => $this->jobId,
            'action' => $this->action,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }
}
