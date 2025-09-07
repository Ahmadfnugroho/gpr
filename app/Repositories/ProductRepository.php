<?php

namespace App\Repositories;

use App\Models\Product;
use App\Services\ResourceCacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }
    
    /**
     * Get relationships that should be eager loaded
     */
    protected function getEagerLoadRelations(): array
    {
        return [
            'category:id,name',
            'brand:id,name', 
            'subCategory:id,name',
            'items:id,product_id,serial_number,is_available'
        ];
    }
    
    /**
     * Get searchable fields for products
     */
    protected function getSearchableFields(): array
    {
        return [
            'name',
            'description'
        ];
    }
    
    /**
     * Get optimized product list with counts
     */
    public function getOptimizedList(int $perPage = 25): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->model->query()
            ->select([
                'products.id',
                'products.name',
                'products.status',
                'products.premiere',
                'products.category_id',
                'products.brand_id',
                'products.sub_category_id',
                'products.price',
                'products.thumbnail'
            ])
            ->withCount(['items as items_count'])
            ->with([
                'category:id,name',
                'brand:id,name',
                'subCategory:id,name'
            ])
            ->orderBy('name')
            ->paginate($perPage);
    }
    
    /**
     * Get products with availability status cached
     */
    public function getWithAvailability(array $productIds = []): Collection
    {
        $cacheKey = 'products_with_availability_' . md5(implode(',', $productIds));
        
        return ResourceCacheService::cacheComplexQuery($cacheKey, function () use ($productIds) {
            $query = $this->model->query()
                ->select([
                    'id',
                    'name',
                    'status',
                    'price',
                    'category_id',
                    'brand_id'
                ])
                ->withCount(['items as total_items'])
                ->withCount(['items as available_items' => function ($query) {
                    $query->where('is_available', true);
                }])
                ->with(['category:id,name', 'brand:id,name']);
                
            if (!empty($productIds)) {
                $query->whereIn('id', $productIds);
            }
            
            return $query->get();
        });
    }
    
    /**
     * Search products with optimized query
     */
    public function searchProducts(string $searchTerm, int $limit = 50): Collection
    {
        $cacheKey = 'product_search_' . md5($searchTerm) . "_{$limit}";
        
        return ResourceCacheService::cacheGlobalSearch(Product::class, $searchTerm, $limit);
    }
    
    /**
     * Get products by category with caching
     */
    public function getByCategory(int $categoryId, int $limit = 100): Collection
    {
        $cacheKey = "products_by_category_{$categoryId}_{$limit}";
        
        return ResourceCacheService::cacheComplexQuery($cacheKey, function () use ($categoryId, $limit) {
            return $this->model->query()
                ->select(['id', 'name', 'price', 'status', 'thumbnail'])
                ->where('category_id', $categoryId)
                ->where('status', 'available')
                ->withCount('items')
                ->orderBy('name')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Get products by status with caching
     */
    public function getByStatus(string $status, int $limit = 100): Collection
    {
        $cacheKey = "products_by_status_{$status}_{$limit}";
        
        return ResourceCacheService::cacheComplexQuery($cacheKey, function () use ($status, $limit) {
            return $this->model->query()
                ->select(['id', 'name', 'price', 'status', 'category_id'])
                ->where('status', $status)
                ->with(['category:id,name'])
                ->withCount('items')
                ->orderBy('name')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Get featured products with caching
     */
    public function getFeatured(int $limit = 20): Collection
    {
        $cacheKey = "featured_products_{$limit}";
        
        return ResourceCacheService::cacheComplexQuery($cacheKey, function () use ($limit) {
            return $this->model->query()
                ->select([
                    'id',
                    'name',
                    'price',
                    'thumbnail',
                    'category_id',
                    'brand_id'
                ])
                ->where('premiere', true)
                ->where('status', 'available')
                ->with(['category:id,name', 'brand:id,name'])
                ->withCount('items')
                ->orderBy('name')
                ->limit($limit)
                ->get();
        }, 60); // Cache featured products for 1 hour
    }
    
    /**
     * Get product statistics
     */
    public function getStatistics(): array
    {
        $cacheKey = 'product_statistics';
        
        return ResourceCacheService::cacheComplexQuery($cacheKey, function () {
            return [
                'total' => $this->model->count(),
                'by_status' => $this->model
                    ->select('status', DB::raw('COUNT(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'by_category' => $this->model
                    ->join('categories', 'products.category_id', '=', 'categories.id')
                    ->select('categories.name', DB::raw('COUNT(*) as count'))
                    ->groupBy('categories.id', 'categories.name')
                    ->pluck('count', 'name')
                    ->toArray(),
                'featured_count' => $this->model->where('premiere', true)->count(),
                'total_items' => DB::table('product_items')->count(),
                'available_items' => DB::table('product_items')->where('is_available', true)->count(),
            ];
        }, 30); // Cache for 30 minutes
    }
    
    /**
     * Get filter options with caching
     */
    public function getFilterOptions(): array
    {
        return [
            'categories' => ResourceCacheService::cacheFilterOptions(
                'filter_options_categories',
                \App\Models\Category::query()
            ),
            'brands' => ResourceCacheService::cacheFilterOptions(
                'filter_options_brands',
                \App\Models\Brand::query()
            ),
            'sub_categories' => ResourceCacheService::cacheFilterOptions(
                'filter_options_sub_categories',
                \App\Models\SubCategory::query()
            ),
            'statuses' => [
                'available' => 'Available',
                'unavailable' => 'Unavailable',
                'maintenance' => 'Maintenance'
            ]
        ];
    }
    
    /**
     * Get products with low stock
     */
    public function getLowStock(int $threshold = 5): Collection
    {
        $cacheKey = "low_stock_products_{$threshold}";
        
        return ResourceCacheService::cacheComplexQuery($cacheKey, function () use ($threshold) {
            return $this->model->query()
                ->select(['id', 'name', 'status', 'category_id'])
                ->withCount(['items as available_items' => function ($query) {
                    $query->where('is_available', true);
                }])
                ->with(['category:id,name'])
                ->having('available_items', '<=', $threshold)
                ->having('available_items', '>', 0)
                ->orderBy('available_items', 'asc')
                ->get();
        });
    }
    
    /**
     * Get products by multiple filters with pagination
     */
    public function getFilteredPaginated(array $filters, int $perPage = 25): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select([
                'products.id',
                'products.name',
                'products.status',
                'products.premiere',
                'products.category_id',
                'products.brand_id',
                'products.sub_category_id',
                'products.price'
            ])
            ->withCount('items')
            ->with(['category:id,name', 'brand:id,name', 'subCategory:id,name']);
            
        // Apply filters
        if (!empty($filters['status'])) {
            $query->whereIn('status', (array) $filters['status']);
        }
        
        if (!empty($filters['category_id'])) {
            $query->whereIn('category_id', (array) $filters['category_id']);
        }
        
        if (!empty($filters['brand_id'])) {
            $query->whereIn('brand_id', (array) $filters['brand_id']);
        }
        
        if (!empty($filters['sub_category_id'])) {
            $query->whereIn('sub_category_id', (array) $filters['sub_category_id']);
        }
        
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where('name', 'LIKE', "%{$searchTerm}%");
        }
        
        if (isset($filters['premiere'])) {
            $query->where('premiere', $filters['premiere']);
        }
        
        return $query->orderBy('name')->paginate($perPage);
    }
    
    /**
     * Bulk update product status
     */
    public function bulkUpdateStatus(array $productIds, string $status): int
    {
        $updated = $this->model->whereIn('id', $productIds)
            ->update(['status' => $status, 'updated_at' => now()]);
            
        // Clear related caches
        $this->clearModelCache();
        ResourceCacheService::invalidateByPattern('product_*');
        ResourceCacheService::invalidateByPattern('filter_options_*');
        
        return $updated;
    }
    
    /**
     * Bulk update featured status
     */
    public function bulkUpdateFeatured(array $productIds, bool $featured): int
    {
        $updated = $this->model->whereIn('id', $productIds)
            ->update(['premiere' => $featured, 'updated_at' => now()]);
            
        // Clear related caches
        $this->clearModelCache();
        ResourceCacheService::invalidateByPattern('product_*');
        ResourceCacheService::invalidateByPattern('featured_*');
        
        return $updated;
    }
    
    /**
     * Get products for rental availability check
     */
    public function getForRentalCheck(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate, array $productIds = []): Collection
    {
        $cacheKey = 'rental_availability_' . md5($startDate->toDateString() . $endDate->toDateString() . implode(',', $productIds));
        
        return ResourceCacheService::cacheComplexQuery($cacheKey, function () use ($startDate, $endDate, $productIds) {
            $query = $this->model->query()
                ->select(['id', 'name', 'status'])
                ->where('status', 'available')
                ->withCount(['items as total_items'])
                ->withCount(['items as available_items' => function ($itemQuery) use ($startDate, $endDate) {
                    $itemQuery->where('is_available', true)
                        ->whereDoesntHave('detailTransactionProductItems', function ($dtpiQuery) use ($startDate, $endDate) {
                            $dtpiQuery->whereHas('detailTransaction.transaction', function ($transactionQuery) use ($startDate, $endDate) {
                                $transactionQuery->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                                    ->where(function ($q) use ($startDate, $endDate) {
                                        $q->whereBetween('start_date', [$startDate, $endDate])
                                            ->orWhereBetween('end_date', [$startDate, $endDate])
                                            ->orWhere(function ($q2) use ($startDate, $endDate) {
                                                $q2->where('start_date', '<=', $startDate)
                                                    ->where('end_date', '>=', $endDate);
                                            });
                                    });
                            });
                        });
                }]);
                
            if (!empty($productIds)) {
                $query->whereIn('id', $productIds);
            }
            
            return $query->get();
        }, 5); // Cache for 5 minutes due to changing availability
    }
}
