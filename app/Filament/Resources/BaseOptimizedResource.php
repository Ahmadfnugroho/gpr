<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

abstract class BaseOptimizedResource extends Resource
{
    /**
     * Cache duration in minutes
     */
    protected static int $cacheMinutes = 10;
    
    /**
     * Whether to use chunking for large datasets
     */
    protected static bool $useChunking = true;
    
    /**
     * Chunk size for processing large datasets
     */
    protected static int $chunkSize = 1000;
    
    /**
     * Default pagination options optimized for performance
     */
    protected static array $paginationOptions = [10, 25, 50];
    
    /**
     * Default pagination page option
     */
    protected static int $defaultPaginationPageOption = 25;
    
    /**
     * Get cached model count
     */
    public static function getCachedCount(?string $cacheKey = null): int
    {
        $cacheKey = $cacheKey ?? static::getCacheKey('count');
        
        return Cache::remember($cacheKey, now()->addMinutes(static::$cacheMinutes), function () {
            return static::getModel()::count();
        });
    }
    
    /**
     * Get cached filtered count
     */
    public static function getCachedFilteredCount(Builder $query, array $filters = []): int
    {
        $cacheKey = static::getCacheKey('filtered_count', ['filters' => md5(serialize($filters))]);
        
        return Cache::remember($cacheKey, now()->addMinutes(static::$cacheMinutes), function () use ($query) {
            return $query->count();
        });
    }
    
    /**
     * Generate cache key
     */
    protected static function getCacheKey(string $type, array $params = []): string
    {
        $modelClass = static::getModel();
        $resourceClass = static::class;
        
        $key = implode('_', [
            'resource',
            str_replace('\\', '_', strtolower($resourceClass)),
            $type
        ]);
        
        if (!empty($params)) {
            $key .= '_' . md5(serialize($params));
        }
        
        return $key;
    }
    
    /**
     * Clear resource cache
     */
    public static function clearCache(): void
    {
        $pattern = static::getCacheKey('*');
        $keys = Cache::getRedis()->keys($pattern);
        
        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }
    }
    
    /**
     * Get optimized query with proper eager loading
     */
    protected static function getOptimizedQuery(): Builder
    {
        $query = static::getModel()::query();
        
        // Apply default optimizations
        $query->select(static::getSelectColumns())
              ->with(static::getEagerLoadRelations());
        
        return $query;
    }
    
    /**
     * Get columns to select (override in child classes)
     */
    protected static function getSelectColumns(): array
    {
        return ['*'];
    }
    
    /**
     * Get relationships to eager load (override in child classes)
     */
    protected static function getEagerLoadRelations(): array
    {
        return [];
    }
    
    /**
     * Process records in chunks for memory efficiency
     */
    protected static function processInChunks(Builder $query, callable $callback): void
    {
        if (static::$useChunking) {
            $query->chunk(static::$chunkSize, $callback);
        } else {
            $records = $query->get();
            $callback($records);
        }
    }
    
    /**
     * Get cached options for filters
     */
    protected static function getCachedFilterOptions(string $relation, string $column, ?string $cacheKey = null): array
    {
        $cacheKey = $cacheKey ?? static::getCacheKey('filter_options', ['relation' => $relation, 'column' => $column]);
        
        return Cache::remember($cacheKey, now()->addMinutes(static::$cacheMinutes * 2), function () use ($relation, $column) {
            return static::getModel()::query()
                ->join($relation, static::getModel()::getTable() . ".{$relation}_id", '=', "{$relation}.id")
                ->distinct()
                ->pluck("{$relation}.{$column}", "{$relation}.id")
                ->toArray();
        });
    }
    
    /**
     * Get database connection info for read/write splitting
     */
    protected static function getReadConnection(): string
    {
        return config('database.connections.mysql_read.database', config('database.default'));
    }
    
    /**
     * Get write database connection
     */
    protected static function getWriteConnection(): string
    {
        return config('database.default');
    }
    
    /**
     * Execute read query on read replica if available
     */
    protected static function executeReadQuery(callable $callback)
    {
        if (config('database.connections.mysql_read')) {
            return DB::connection('mysql_read')->transaction($callback);
        }
        
        return DB::transaction($callback);
    }
    
    /**
     * Execute write query on master database
     */
    protected static function executeWriteQuery(callable $callback)
    {
        return DB::connection(static::getWriteConnection())->transaction($callback);
    }
    
    /**
     * Get performance monitoring data
     */
    public static function getPerformanceStats(): array
    {
        return [
            'cache_hits' => Cache::get(static::getCacheKey('cache_hits'), 0),
            'query_count' => DB::getQueryLog() ? count(DB::getQueryLog()) : 0,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ];
    }
    
    /**
     * Log slow queries for monitoring
     */
    protected static function logSlowQuery(string $query, float $executionTime): void
    {
        if ($executionTime > config('app.slow_query_threshold', 1.0)) {
            \Log::warning('Slow query detected', [
                'resource' => static::class,
                'query' => $query,
                'execution_time' => $executionTime,
                'memory_usage' => memory_get_usage(true),
            ]);
        }
    }
}
