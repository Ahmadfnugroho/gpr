<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ResourceCacheService
{
    /**
     * Default cache duration in minutes
     */
    protected const DEFAULT_TTL = 15;
    
    /**
     * Long term cache duration for static data
     */
    protected const LONG_TTL = 60;
    
    /**
     * Short term cache for frequently changing data
     */
    protected const SHORT_TTL = 5;
    
    /**
     * Cache filter options with long TTL
     */
    public static function cacheFilterOptions(string $key, Builder|array $query, int $ttl = self::LONG_TTL): array
    {
        return Cache::remember($key, now()->addMinutes($ttl), function () use ($query) {
            if (is_array($query)) {
                return $query;
            }
            return $query->pluck('name', 'id')->toArray();
        });
    }
    
    /**
     * Cache table data with pagination
     */
    public static function cacheTableData(string $key, Builder $query, int $page = 1, int $perPage = 25, int $ttl = self::DEFAULT_TTL): array
    {
        $cacheKey = "{$key}_page_{$page}_per_{$perPage}";
        
        return Cache::remember($cacheKey, now()->addMinutes($ttl), function () use ($query, $page, $perPage) {
            $offset = ($page - 1) * $perPage;
            
            return [
                'data' => $query->offset($offset)->limit($perPage)->get(),
                'total' => $query->count(),
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($query->count() / $perPage),
            ];
        });
    }
    
    /**
     * Cache global search results
     */
    public static function cacheGlobalSearch(string $model, string $searchTerm, int $limit = 50): Collection
    {
        $key = "global_search_{$model}_" . md5($searchTerm) . "_{$limit}";
        
        return Cache::remember($key, now()->addMinutes(self::SHORT_TTL), function () use ($model, $searchTerm, $limit) {
            return $model::where('name', 'LIKE', "%{$searchTerm}%")
                ->limit($limit)
                ->get(['id', 'name']);
        });
    }
    
    /**
     * Cache related data counts
     */
    public static function cacheRelatedCounts(string $key, callable $countCallback, int $ttl = self::DEFAULT_TTL): int
    {
        return Cache::remember($key, now()->addMinutes($ttl), $countCallback);
    }
    
    /**
     * Cache complex query results
     */
    public static function cacheComplexQuery(string $key, callable $queryCallback, int $ttl = self::DEFAULT_TTL)
    {
        return Cache::remember($key, now()->addMinutes($ttl), $queryCallback);
    }
    
    /**
     * Invalidate cache by pattern
     */
    public static function invalidateByPattern(string $pattern): int
    {
        try {
            if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
                $redis = Cache::getRedis();
                $keys = $redis->keys($pattern);
                
                if (!empty($keys)) {
                    return $redis->del($keys);
                }
            } else {
                // For non-Redis stores, we can't easily invalidate by pattern
                Log::warning('Cache pattern invalidation not supported for current cache driver');
            }
        } catch (\Exception $e) {
            Log::error('Failed to invalidate cache pattern: ' . $e->getMessage());
        }
        
        return 0;
    }
    
    /**
     * Warm up common cache entries
     */
    public static function warmUpCache(): void
    {
        try {
            // Warm up common filter options
            self::warmUpFilterOptions();
            
            // Warm up frequently accessed data
            self::warmUpFrequentData();
            
            Log::info('Cache warm-up completed successfully');
        } catch (\Exception $e) {
            Log::error('Cache warm-up failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Warm up filter options cache
     */
    protected static function warmUpFilterOptions(): void
    {
        $filterData = [
            'categories' => \App\Models\Category::pluck('name', 'id')->toArray(),
            'brands' => \App\Models\Brand::pluck('name', 'id')->toArray(),
            'sub_categories' => \App\Models\SubCategory::pluck('name', 'id')->toArray(),
        ];
        
        foreach ($filterData as $key => $data) {
            Cache::put("filter_options_{$key}", $data, now()->addMinutes(self::LONG_TTL));
        }
    }
    
    /**
     * Warm up frequently accessed data
     */
    protected static function warmUpFrequentData(): void
    {
        // Cache product counts by status
        $productCounts = [
            'available' => \App\Models\Product::where('status', 'available')->count(),
            'unavailable' => \App\Models\Product::where('status', 'unavailable')->count(),
            'maintenance' => \App\Models\Product::where('status', 'maintenance')->count(),
        ];
        
        Cache::put('product_status_counts', $productCounts, now()->addMinutes(self::DEFAULT_TTL));
        
        // Cache recent transactions count
        $recentTransactionsCount = \App\Models\Transaction::where('created_at', '>=', now()->subDays(30))
            ->count();
        
        Cache::put('recent_transactions_count', $recentTransactionsCount, now()->addMinutes(self::DEFAULT_TTL));
    }
    
    /**
     * Get cache statistics
     */
    public static function getCacheStats(): array
    {
        $stats = [
            'cache_driver' => config('cache.default'),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
        
        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            try {
                $redis = Cache::getRedis();
                $info = $redis->info();
                
                $stats['redis_memory'] = $info['used_memory_human'] ?? 'N/A';
                $stats['redis_keys'] = $info['db0']['keys'] ?? 0;
            } catch (\Exception $e) {
                $stats['redis_error'] = $e->getMessage();
            }
        }
        
        return $stats;
    }
    
    /**
     * Clear all resource caches
     */
    public static function clearAllResourceCaches(): void
    {
        $patterns = [
            'resource_*',
            'filter_options_*',
            'global_search_*',
            'product_*',
            'transaction_*',
            'customer_*'
        ];
        
        foreach ($patterns as $pattern) {
            self::invalidateByPattern($pattern);
        }
        
        Log::info('All resource caches cleared');
    }
    
    /**
     * Schedule cache refresh for specific resource
     */
    public static function scheduleRefresh(string $resourceClass, int $delayMinutes = 5): void
    {
        // This would typically dispatch a job to refresh cache
        // For now, we'll just log the intention
        Log::info("Cache refresh scheduled for {$resourceClass} in {$delayMinutes} minutes");
    }
}
