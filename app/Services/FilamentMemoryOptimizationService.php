<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class FilamentMemoryOptimizationService
{
    /**
     * Get optimal page size based on available memory
     */
    public static function getOptimalPageSize(): int
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = self::parseMemoryLimit($memoryLimit);
        
        // Use 60% of available memory for safety
        $safeMemory = $memoryLimitBytes * 0.6;
        
        // Estimate ~50KB per record on average (including relations)
        $estimatedRecordSize = 50 * 1024;
        
        $optimalRecords = intval($safeMemory / $estimatedRecordSize);
        
        // Set reasonable bounds
        $minRecords = 10;
        $maxRecords = 100;
        
        return max($minRecords, min($maxRecords, $optimalRecords));
    }
    
    /**
     * Get chunk size for processing large datasets
     */
    public static function getOptimalChunkSize(): int
    {
        return max(25, intval(self::getOptimalPageSize() / 4));
    }
    
    /**
     * Parse memory limit string to bytes
     */
    private static function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = intval($memoryLimit);
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Create memory-efficient query builder
     */
    public static function optimizeQuery(Builder $query): Builder
    {
        return $query
            ->select(['*']) // Only select needed columns
            ->limit(self::getOptimalPageSize())
            ->orderBy('id', 'desc'); // Always have consistent ordering
    }
    
    /**
     * Create memory-efficient paginator
     */
    public static function createOptimizedPaginator(
        Builder $query, 
        int $currentPage = 1,
        ?int $pageSize = null
    ): LengthAwarePaginator {
        
        $pageSize = $pageSize ?? self::getOptimalPageSize();
        $offset = ($currentPage - 1) * $pageSize;
        
        // Use separate query for count to avoid memory issues
        $total = $query->toBase()->getCountForPagination();
        
        // Get only the records for current page
        $items = $query->offset($offset)->limit($pageSize)->get();
        
        return new LengthAwarePaginator(
            $items,
            $total,
            $pageSize,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );
    }
    
    /**
     * Process large dataset in memory-safe chunks
     */
    public static function processInChunks(
        Builder $query,
        callable $callback,
        ?int $chunkSize = null
    ): void {
        $chunkSize = $chunkSize ?? self::getOptimalChunkSize();
        
        $query->chunk($chunkSize, function ($items) use ($callback) {
            $callback($items);
            
            // Force garbage collection after each chunk
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        });
    }
    
    /**
     * Get memory usage information
     */
    public static function getMemoryUsage(): array
    {
        return [
            'current_usage' => memory_get_usage(true),
            'current_usage_formatted' => self::formatBytes(memory_get_usage(true)),
            'peak_usage' => memory_get_peak_usage(true),
            'peak_usage_formatted' => self::formatBytes(memory_get_peak_usage(true)),
            'limit' => ini_get('memory_limit'),
            'limit_bytes' => self::parseMemoryLimit(ini_get('memory_limit')),
            'usage_percentage' => round((memory_get_usage(true) / self::parseMemoryLimit(ini_get('memory_limit'))) * 100, 2),
            'optimal_page_size' => self::getOptimalPageSize(),
            'optimal_chunk_size' => self::getOptimalChunkSize(),
        ];
    }
    
    /**
     * Format bytes to human readable format
     */
    private static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
    
    /**
     * Clear various caches to free memory
     */
    public static function clearMemory(): void
    {
        // Clear query cache
        DB::getQueryLog();
        
        // Clear view cache if possible
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        // Clear Laravel caches
        if (app()->bound('cache')) {
            Cache::flush();
        }
    }
    
    /**
     * Check if current memory usage is approaching limit
     */
    public static function isMemoryLimitApproaching(float $threshold = 0.8): bool
    {
        $current = memory_get_usage(true);
        $limit = self::parseMemoryLimit(ini_get('memory_limit'));
        
        return ($current / $limit) > $threshold;
    }
    
    /**
     * Get recommended settings for current environment
     */
    public static function getRecommendedSettings(): array
    {
        $memoryInfo = self::getMemoryUsage();
        
        return [
            'pagination_size' => self::getOptimalPageSize(),
            'chunk_size' => self::getOptimalChunkSize(),
            'lazy_loading' => true,
            'cache_queries' => $memoryInfo['limit_bytes'] > (128 * 1024 * 1024), // Only if > 128MB
            'enable_query_log' => false, // Disable in production
            'memory_info' => $memoryInfo,
            'recommendations' => self::getMemoryRecommendations($memoryInfo),
        ];
    }
    
    /**
     * Get memory optimization recommendations
     */
    private static function getMemoryRecommendations(array $memoryInfo): array
    {
        $recommendations = [];
        
        if ($memoryInfo['usage_percentage'] > 80) {
            $recommendations[] = 'Memory usage is high (' . $memoryInfo['usage_percentage'] . '%). Consider reducing page size.';
        }
        
        if ($memoryInfo['limit_bytes'] < (256 * 1024 * 1024)) {
            $recommendations[] = 'Memory limit is low (' . $memoryInfo['limit'] . '). Consider increasing to at least 256M.';
        }
        
        if ($memoryInfo['optimal_page_size'] < 25) {
            $recommendations[] = 'Very small optimal page size detected. Consider increasing memory limit.';
        }
        
        return $recommendations;
    }
    
    /**
     * Create optimized table query for Filament
     */
    public static function createFilamentTableQuery(Builder $baseQuery): Builder
    {
        return $baseQuery
            ->select(['*']) // Select only what's needed
            ->when(self::isMemoryLimitApproaching(0.7), function ($query) {
                // If memory is getting low, be more aggressive with limits
                return $query->limit(self::getOptimalPageSize() / 2);
            })
            ->orderBy('id', 'desc');
    }
}
