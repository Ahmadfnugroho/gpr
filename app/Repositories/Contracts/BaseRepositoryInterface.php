<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BaseRepositoryInterface
{
    /**
     * Get all records
     */
    public function all(array $columns = ['*']): Collection;
    
    /**
     * Get paginated records
     */
    public function paginate(int $perPage = 25, array $columns = ['*']): LengthAwarePaginator;
    
    /**
     * Find record by ID
     */
    public function find(int $id, array $columns = ['*']): ?Model;
    
    /**
     * Find record by ID or fail
     */
    public function findOrFail(int $id, array $columns = ['*']): Model;
    
    /**
     * Find records by criteria
     */
    public function findBy(array $criteria, array $columns = ['*']): Collection;
    
    /**
     * Find first record by criteria
     */
    public function findOneBy(array $criteria, array $columns = ['*']): ?Model;
    
    /**
     * Create new record
     */
    public function create(array $data): Model;
    
    /**
     * Update record
     */
    public function update(int $id, array $data): Model;
    
    /**
     * Delete record
     */
    public function delete(int $id): bool;
    
    /**
     * Get query builder
     */
    public function query(): Builder;
    
    /**
     * Get with relationships
     */
    public function with(array $relations): static;
    
    /**
     * Apply scopes
     */
    public function scope(string $scope, ...$parameters): static;
    
    /**
     * Count records
     */
    public function count(array $criteria = []): int;
    
    /**
     * Get records in chunks
     */
    public function chunk(int $size, callable $callback): bool;
    
    /**
     * Get records using cursor
     */
    public function cursor(): \Illuminate\Support\LazyCollection;
    
    /**
     * Search records
     */
    public function search(string $term, array $columns = ['*']): Collection;
    
    /**
     * Get filtered records
     */
    public function filter(array $filters, array $columns = ['*']): Collection;
    
    /**
     * Get cached results
     */
    public function cached(string $key, callable $callback, int $minutes = 15);
}
