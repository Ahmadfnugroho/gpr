<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductItem;
use App\Models\Brand;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    /**
     * Get products with advanced filtering and caching
     */
    public function getProducts(array $filters = [], int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        $cacheKey = $this->generateProductCacheKey($filters, $page, $limit);
        
        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($filters, $page, $limit) {
            $query = Product::query()
                ->with([
                    'brand:id,name,slug',
                    'category:id,name,slug',
                    'subCategory:id,name,slug',
                    'productPhotos:id,product_id,photo',
                    'productSpecifications',
                    'rentalIncludes.includedProduct:id,name,slug'
                ]);

            // Apply filters
            $this->applyFilters($query, $filters);

            // Apply sorting
            $this->applySorting($query, $filters);

            return $query->paginate($limit, ['*'], 'page', $page);
        });
    }

    /**
     * Get product availability for specific date range
     */
    public function getProductAvailability(Product $product, Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = "product_detailed_availability_{$product->id}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($product, $startDate, $endDate) {
            $totalItems = $product->items()->count();
            $availableQuantity = $product->getAvailableQuantityForPeriod($startDate, $endDate);
            $bookedQuantity = $totalItems - $availableQuantity;
            
            // Get booked items details
            $bookedItems = $product->items()
                ->whereHas('detailTransactions.transaction', function ($q) use ($startDate, $endDate) {
                    $q->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                        ->where(function ($q2) use ($startDate, $endDate) {
                            $q2->whereBetween('start_date', [$startDate, $endDate])
                                ->orWhereBetween('end_date', [$startDate, $endDate])
                                ->orWhere(function ($q3) use ($startDate, $endDate) {
                                    $q3->where('start_date', '<', $startDate)
                                        ->where('end_date', '>', $endDate);
                                });
                        });
                })
                ->with('detailTransactions.transaction:id,booking_transaction_id,booking_status,start_date,end_date')
                ->get(['id', 'serial_number']);

            return [
                'total_items' => $totalItems,
                'available_quantity' => $availableQuantity,
                'booked_quantity' => $bookedQuantity,
                'availability_percentage' => $totalItems > 0 ? round(($availableQuantity / $totalItems) * 100, 2) : 0,
                'available_serials' => $product->getAvailableSerialNumbersForPeriod($startDate->format('Y-m-d'), $endDate->format('Y-m-d')),
                'booked_items' => $bookedItems,
                'next_available_date' => $this->getNextAvailableDate($product),
            ];
        });
    }

    /**
     * Get product search suggestions with caching
     */
    public function getSearchSuggestions(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        $cacheKey = "product_suggestions_" . md5($query);
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($query) {
            // Use optimized single UNION query
            return DB::select("
                (SELECT 'product' as type, name, slug, thumbnail, 
                        CONCAT('/product/', slug) as url, 
                        name as display
                 FROM products 
                 WHERE MATCH(name) AGAINST(? IN NATURAL LANGUAGE MODE)
                 ORDER BY MATCH(name) AGAINST(? IN NATURAL LANGUAGE MODE) DESC
                 LIMIT 5)
                UNION ALL
                (SELECT 'category' as type, name, slug, NULL as thumbnail,
                        CONCAT('/browse-product?category=', slug) as url,
                        CONCAT('Kategori: ', name) as display
                 FROM categories
                 WHERE name LIKE ?
                 LIMIT 3)
                UNION ALL
                (SELECT 'brand' as type, name, slug, logo as thumbnail,
                        CONCAT('/browse-product?brand=', slug) as url,
                        CONCAT('Brand: ', name) as display
                 FROM brands
                 WHERE name LIKE ?
                 LIMIT 3)
            ", [$query, $query, "%{$query}%", "%{$query}%"]);
        });
    }

    /**
     * Get popular products based on rental frequency
     */
    public function getPopularProducts(int $limit = 10): \Illuminate\Support\Collection
    {
        return Cache::remember("popular_products_{$limit}", now()->addHours(2), function () use ($limit) {
            return Product::with(['brand', 'category', 'productPhotos'])
                ->withCount(['detailTransactions as rental_count' => function ($query) {
                    $query->whereHas('transaction', function ($q) {
                        $q->where('booking_status', '!=', 'cancel');
                    });
                }])
                ->having('rental_count', '>', 0)
                ->orderByDesc('rental_count')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get products by category with availability info
     */
    public function getProductsByCategory(int $categoryId, Carbon $startDate, Carbon $endDate): \Illuminate\Support\Collection
    {
        $cacheKey = "category_products_{$categoryId}_{$startDate->format('Y-m-d')}_{$endDate->format('Y-m-d')}";
        
        return Cache::remember($cacheKey, now()->addMinutes(15), function () use ($categoryId, $startDate, $endDate) {
            $products = Product::with(['brand', 'productPhotos'])
                ->where('category_id', $categoryId)
                ->get();

            return $products->map(function ($product) use ($startDate, $endDate) {
                $availability = $this->getProductAvailability($product, $startDate, $endDate);
                $product->availability_info = $availability;
                return $product;
            });
        });
    }

    /**
     * Bulk update product availability
     */
    public function bulkUpdateAvailability(array $productIds, bool $isAvailable): int
    {
        $updated = ProductItem::whereIn('product_id', $productIds)
            ->update(['is_available' => $isAvailable]);

        // Clear related caches
        foreach ($productIds as $productId) {
            Cache::forget("product_availability_{$productId}_*");
            Cache::forget("product_serials_{$productId}_*");
        }

        Cache::forget('popular_products_*');
        Cache::forget('product_suggestions_*');

        return $updated;
    }

    /**
     * Generate cache key for product queries
     */
    protected function generateProductCacheKey(array $filters, int $page, int $limit): string
    {
        $filterHash = md5(serialize($filters));
        return "products_filtered_{$filterHash}_page_{$page}_limit_{$limit}";
    }

    /**
     * Apply filters to product query
     */
    protected function applyFilters(\Illuminate\Database\Eloquent\Builder $query, array $filters): void
    {
        // Search filter
        if (!empty($filters['q'])) {
            $search = $filters['q'];
            $query->where('name', 'like', "%{$search}%");
        }

        // Category filter
        if (!empty($filters['category'])) {
            $categories = is_array($filters['category']) ? $filters['category'] : [$filters['category']];
            $query->whereHas('category', fn($q) => $q->whereIn('slug', $categories));
        }

        // Brand filter
        if (!empty($filters['brand'])) {
            $brands = is_array($filters['brand']) ? $filters['brand'] : [$filters['brand']];
            $query->whereHas('brand', fn($q) => $q->whereIn('slug', $brands));
        }

        // Premiere filter
        if (isset($filters['premiere'])) {
            $query->where('premiere', (bool)$filters['premiere']);
        }

        // Status filter
        if (!empty($filters['available'])) {
            $statuses = is_array($filters['available']) ? $filters['available'] : [$filters['available']];
            $query->whereIn('status', $statuses);
        }

        // Exclude rental includes
        if (!empty($filters['exclude_rental_includes'])) {
            $query->whereNotIn('id', function ($subQuery) {
                $subQuery->select('include_product_id')
                    ->from('rental_includes')
                    ->whereNotNull('include_product_id');
            });
        }
    }

    /**
     * Apply sorting to product query
     */
    protected function applySorting(\Illuminate\Database\Eloquent\Builder $query, array $filters): void
    {
        $sort = $filters['sort'] ?? 'name';
        $order = $filters['order'] ?? 'asc';

        switch ($sort) {
            case 'recommended':
                $query->orderBy('premiere', 'desc');
                break;
            case 'latest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'availability':
                $query->orderByRaw("FIELD(status, 'available') DESC");
                break;
            case 'popular':
                $query->withCount(['detailTransactions as rental_count'])
                    ->orderByDesc('rental_count');
                break;
            default:
                if (in_array($sort, ['name', 'price']) && in_array($order, ['asc', 'desc'])) {
                    $query->orderBy($sort, $order);
                } else {
                    $query->orderBy('name', 'asc');
                }
        }
    }

    /**
     * Get next available date for a product
     */
    protected function getNextAvailableDate(Product $product): ?string
    {
        $nextRental = $product->detailTransactions()
            ->whereHas('transaction', function ($query) {
                $query->where('booking_status', '!=', 'cancel')
                    ->where('end_date', '>', now());
            })
            ->with('transaction:id,end_date')
            ->orderBy('end_date')
            ->first();

        return $nextRental ? 
            Carbon::parse($nextRental->transaction->end_date)->addDay()->format('Y-m-d') : 
            now()->format('Y-m-d');
    }
}
