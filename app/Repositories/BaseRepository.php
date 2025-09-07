<?php

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Services\ResourceCacheService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;
    protected array $with = [];
    protected array $scopes = [];
    protected bool $cachingEnabled = true;
    protected int $defaultCacheMinutes = 15;
    
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
    
    /**
     * Get all records
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->buildQuery()->get($columns);
    }
    
    /**
     * Get paginated records
     */
    public function paginate(int $perPage = 25, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->buildQuery()->paginate($perPage, $columns);
    }
    
    /**
     * Find record by ID
     */
    public function find(int $id, array $columns = ['*']): ?Model
    {
        if ($this->cachingEnabled) {
            $cacheKey = $this->getCacheKey('find', ['id' => $id, 'columns' => $columns]);
            
            return Cache::remember($cacheKey, now()->addMinutes($this->defaultCacheMinutes), function () use ($id, $columns) {
                return $this->buildQuery()->find($id, $columns);
            });
        }
        
        return $this->buildQuery()->find($id, $columns);
    }
    
    /**
     * Find record by ID or fail
     */
    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->buildQuery()->findOrFail($id, $columns);
    }
    
    /**
     * Find records by criteria
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection
    {
        $query = $this->buildQuery();
        
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->get($columns);
    }
    
    /**
     * Find first record by criteria
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model
    {
        $query = $this->buildQuery();
        
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->first($columns);
    }
    
    /**
     * Create new record
     */
    public function create(array $data): Model
    {
        $record = $this->model->newInstance()->create($data);
        
        // Clear related caches
        $this->clearModelCache();
        
        return $record;
    }
    
    /**
     * Update record
     */
    public function update(int $id, array $data): Model
    {
        $record = $this->findOrFail($id);
        $record->update($data);
        
        // Clear related caches
        $this->clearModelCache();
        
        return $record;
    }
    
    /**
     * Delete record
     */
    public function delete(int $id): bool
    {
        $record = $this->findOrFail($id);
        $result = $record->delete();
        
        // Clear related caches
        $this->clearModelCache();
        
        return $result;
    }
    
    /**
     * Get query builder
     */
    public function query(): Builder
    {
        return $this->buildQuery();
    }
    
    /**
     * Get with relationships
     */
    public function with(array $relations): static
    {
        $this->with = array_merge($this->with, $relations);
        return $this;
    }
    
    /**
     * Apply scopes
     */
    public function scope(string $scope, ...$parameters): static
    {
        $this->scopes[] = [$scope, $parameters];
        return $this;
    }
    
    /**
     * Count records
     */
    public function count(array $criteria = []): int
    {
        if ($this->cachingEnabled && empty($criteria)) {
            $cacheKey = $this->getCacheKey('count');
            
            return Cache::remember($cacheKey, now()->addMinutes($this->defaultCacheMinutes), function () {
                return $this->buildQuery()->count();
            });
        }
        
        $query = $this->buildQuery();
        
        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->count();
    }
    
    /**
     * Get records in chunks
     */
    public function chunk(int $size, callable $callback): bool
    {
        return $this->buildQuery()->chunk($size, $callback);
    }
    
    /**
     * Get records using cursor
     */
    public function cursor(): \Illuminate\Support\LazyCollection
    {
        return $this->buildQuery()->cursor();
    }
    
    /**
     * Search records
     */
    public function search(string $term, array $columns = ['*']): Collection
    {
        $searchableFields = $this->getSearchableFields();
        $query = $this->buildQuery();
        
        $query->where(function ($q) use ($term, $searchableFields) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'LIKE', "%{$term}%");
            }
        });
        
        return $query->get($columns);
    }
    
    /**
     * Get filtered records
     */
    public function filter(array $filters, array $columns = ['*']): Collection
    {
        $query = $this->buildQuery();
        
        foreach ($filters as $field => $value) {
            if (is_null($value) || $value === '') {
                continue;
            }
            
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } elseif (strpos($value, '%') !== false) {
                $query->where($field, 'LIKE', $value);
            } else {
                $query->where($field, $value);
            }
        }
        
        return $query->get($columns);
    }
    
    /**
     * Get cached results
     */
    public function cached(string $key, callable $callback, int $minutes = 15)
    {
        if (!$this->cachingEnabled) {
            return $callback();
        }
        
        $fullKey = $this->getCacheKey($key);
        
        return Cache::remember($fullKey, now()->addMinutes($minutes), $callback);
    }
    
    /**
     * Build query with eager loading and scopes
     */
    protected function buildQuery(): Builder
    {
        $query = $this->model->newQuery();
        
        // Apply eager loading
        if (!empty($this->with)) {
            $query->with($this->with);
        }
        
        // Apply scopes
        foreach ($this->scopes as [$scope, $parameters]) {
            $query->{$scope}(...$parameters);
        }
        
        return $query;
    }
    
    /**
     * Get cache key for this repository
     */
    protected function getCacheKey(string $method, array $params = []): string
    {
        $modelClass = get_class($this->model);
        $repositoryClass = static::class;
        
        $key = implode('_', [
            'repository',
            str_replace('\\', '_', strtolower($modelClass)),
            str_replace('\\', '_', strtolower($repositoryClass)),
            $method
        ]);
        
        if (!empty($params)) {
            $key .= '_' . md5(serialize($params));
        }
        
        return $key;
    }
    
    /**
     * Clear model-related caches
     */
    protected function clearModelCache(): void
    {
        if (!$this->cachingEnabled) {
            return;
        }
        
        $modelClass = get_class($this->model);
        $pattern = "repository_" . str_replace('\\', '_', strtolower($modelClass)) . "_*";
        
        ResourceCacheService::invalidateByPattern($pattern);
    }
    
    /**
     * Get searchable fields for this model
     */
    protected function getSearchableFields(): array
    {
        // Override this method in child repositories
        return ['name'];
    }
    
    /**
     * Enable or disable caching
     */
    public function enableCaching(bool $enabled = true): static
    {
        $this->cachingEnabled = $enabled;
        return $this;
    }
    
    /**
     * Set cache duration
     */
    public function setCacheMinutes(int $minutes): static
    {
        $this->defaultCacheMinutes = $minutes;
        return $this;
    }
    
    /**
     * Reset query modifiers
     */
    public function fresh(): static
    {
        $this->with = [];
        $this->scopes = [];
        return $this;
    }
    
    /**
     * Get performance optimized results
     */
    public function optimized(): static
    {
        // Add common optimizations
        $this->with($this->getEagerLoadRelations());
        return $this;
    }
    
    /**
     * Get relationships that should be eager loaded
     */
    protected function getEagerLoadRelations(): array
    {
        // Override this method in child repositories
        return [];
    }
    
    /**
     * Execute query with performance monitoring
     */
    protected function executeWithMonitoring(callable $callback)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        try {
            $result = $callback();
            
            $executionTime = microtime(true) - $startTime;
            $memoryUsage = memory_get_usage(true) - $startMemory;
            
            // Log slow queries
            if ($executionTime > 1.0) { // 1 second threshold
                \Log::warning('Slow repository query detected', [
                    'repository' => static::class,
                    'execution_time' => $executionTime,
                    'memory_usage' => $memoryUsage,
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            \Log::error('Repository query failed', [
                'repository' => static::class,
                'error' => $e->getMessage(),
                'execution_time' => microtime(true) - $startTime,
            ]);
            
            throw $e;
        }
    }
}
