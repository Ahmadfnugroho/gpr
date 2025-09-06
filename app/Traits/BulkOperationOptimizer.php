<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

trait BulkOperationOptimizer
{
    /**
     * Process data in optimized chunks
     */
    protected function processInChunks(array $data, int $chunkSize = 100, callable $processor = null): array
    {
        $chunks = array_chunk($data, $chunkSize);
        $results = [];
        $totalProcessed = 0;
        
        foreach ($chunks as $index => $chunk) {
            // Process the chunk
            if ($processor) {
                $chunkResult = $processor($chunk, $index);
                $results[] = $chunkResult;
                $totalProcessed += count($chunk);
            }
            
            // Memory management every 10 chunks
            if ($index % 10 === 0) {
                $this->optimizeMemory();
            }
            
            // Log progress every 50 chunks
            if ($index % 50 === 0) {
                Log::info("Bulk operation progress", [
                    'chunks_processed' => $index + 1,
                    'total_chunks' => count($chunks),
                    'records_processed' => $totalProcessed,
                    'memory_usage' => $this->getMemoryUsage()
                ]);
            }
        }
        
        return $results;
    }

    /**
     * Optimize memory usage during bulk operations
     */
    protected function optimizeMemory(): void
    {
        // Clear Laravel's query log to prevent memory leaks
        DB::flushQueryLog();
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        // Clear any expired cache entries
        if (method_exists(Cache::getStore(), 'flush')) {
            // Only clear expired entries, not all cache
            $this->clearExpiredCache();
        }
    }

    /**
     * Get current memory usage information
     */
    protected function getMemoryUsage(): array
    {
        return [
            'current' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB',
            'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB',
            'limit' => ini_get('memory_limit')
        ];
    }

    /**
     * Check if memory limit is approaching
     */
    protected function isMemoryLimitApproaching(float $threshold = 0.8): bool
    {
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $currentUsage = memory_get_usage(true);
        
        return ($currentUsage / $memoryLimit) > $threshold;
    }

    /**
     * Parse memory limit string to bytes
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = strtoupper($memoryLimit);
        $bytes = (int) $memoryLimit;
        
        if (strpos($memoryLimit, 'G') !== false) {
            $bytes *= 1024 * 1024 * 1024;
        } elseif (strpos($memoryLimit, 'M') !== false) {
            $bytes *= 1024 * 1024;
        } elseif (strpos($memoryLimit, 'K') !== false) {
            $bytes *= 1024;
        }
        
        return $bytes;
    }

    /**
     * Clear expired cache entries
     */
    private function clearExpiredCache(): void
    {
        try {
            // This is implementation specific - for Redis/Memcached
            // For file cache, Laravel automatically handles cleanup
            $cacheStore = Cache::getStore();
            
            if (method_exists($cacheStore, 'getRedis')) {
                // Redis-specific cleanup if needed
                // $cacheStore->getRedis()->eval("return redis.call('del', unpack(redis.call('keys', ARGV[1])))", 0, 'bulk_job_progress_*');
            }
        } catch (\Exception $e) {
            // Silent fail for cache cleanup
            Log::debug('Cache cleanup failed: ' . $e->getMessage());
        }
    }

    /**
     * Execute bulk database operation with transaction safety
     */
    protected function executeBulkOperation(callable $operation, array $data, int $chunkSize = 100): array
    {
        $results = [];
        $totalSuccess = 0;
        $totalFailed = 0;
        $errors = [];

        try {
            DB::beginTransaction();

            $this->processInChunks($data, $chunkSize, function ($chunk, $index) use ($operation, &$results, &$totalSuccess, &$totalFailed, &$errors) {
                try {
                    $result = $operation($chunk);
                    $results[] = $result;
                    $totalSuccess += is_array($result) ? count($result) : ($result ?: 0);
                    
                    return $result;
                } catch (\Exception $e) {
                    $totalFailed += count($chunk);
                    $errors[] = "Chunk {$index}: " . $e->getMessage();
                    
                    Log::error("Bulk operation chunk failed", [
                        'chunk_index' => $index,
                        'chunk_size' => count($chunk),
                        'error' => $e->getMessage()
                    ]);
                    
                    // Decide whether to continue or fail completely
                    if ($this->shouldFailCompletely($e)) {
                        throw $e;
                    }
                    
                    return 0;
                }
            });

            DB::commit();

            return [
                'success' => $totalSuccess,
                'failed' => $totalFailed,
                'total' => $totalSuccess + $totalFailed,
                'errors' => $errors,
                'results' => $results
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error("Bulk operation failed completely", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Determine if an exception should cause complete operation failure
     */
    private function shouldFailCompletely(\Exception $e): bool
    {
        // Fail completely for critical database errors
        $criticalErrors = [
            'database connection',
            'table doesn\'t exist',
            'column not found',
            'foreign key constraint'
        ];

        $errorMessage = strtolower($e->getMessage());
        
        foreach ($criticalErrors as $critical) {
            if (strpos($errorMessage, $critical) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a cache key for bulk operations
     */
    protected function getBulkCacheKey(string $operation, array $identifiers): string
    {
        $hash = md5(serialize($identifiers));
        return "bulk_{$operation}_{$hash}";
    }

    /**
     * Cache bulk operation results temporarily
     */
    protected function cacheBulkResult(string $key, array $result, int $ttl = 3600): void
    {
        try {
            Cache::put($key, $result, $ttl);
        } catch (\Exception $e) {
            // Silent fail for caching
            Log::debug('Bulk result caching failed: ' . $e->getMessage());
        }
    }

    /**
     * Get cached bulk operation result
     */
    protected function getCachedBulkResult(string $key): ?array
    {
        try {
            return Cache::get($key);
        } catch (\Exception $e) {
            // Silent fail for cache retrieval
            Log::debug('Bulk result cache retrieval failed: ' . $e->getMessage());
            return null;
        }
    }
}
