<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Services\ResourceCacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TransactionRepository extends BaseRepository
{
    public function __construct(Transaction $model)
    {
        parent::__construct($model);
    }
    
    /**
     * Get relationships that should be eager loaded
     */
    protected function getEagerLoadRelations(): array
    {
        return [
            'customer:id,name,email',
            'customer.customerPhoneNumbers:id,customer_id,phone_number',
            'detailTransactions:id,transaction_id,product_id,bundling_id,quantity',
            'detailTransactions.product:id,name,price',
            'detailTransactions.bundling:id,name,price',
            'detailTransactions.bundling.bundlingProducts:id,bundling_id,product_id,quantity',
            'detailTransactions.bundling.bundlingProducts.product:id,name,price',
            'detailTransactions.productItems:id,serial_number,product_id',
            'promo:id,name,type,rules'
        ];
    }
    
    /**
     * Get searchable fields for transactions
     */
    protected function getSearchableFields(): array
    {
        return [
            'booking_transaction_id',
            'customer.name',
            'customer.email'
        ];
    }
    
    /**
     * Search transactions with optimized query
     */
    public function searchTransactions(string $searchTerm, int $perPage = 50): \Illuminate\Pagination\LengthAwarePaginator
    {
        $cacheKey = "transaction_search_" . md5($searchTerm) . "_{$perPage}";
        
        return ResourceCacheService::cacheComplexQuery($cacheKey, function () use ($searchTerm, $perPage) {
            return $this->model->query()
                ->select([
                    'transactions.id',
                    'transactions.booking_transaction_id',
                    'transactions.customer_id',
                    'transactions.start_date',
                    'transactions.end_date',
                    'transactions.grand_total',
                    'transactions.booking_status',
                    'transactions.created_at'
                ])
                ->with($this->getEagerLoadRelations())
                ->where(function ($query) use ($searchTerm) {
                    $query->where('booking_transaction_id', 'LIKE', "%{$searchTerm}%")
                        ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                            $customerQuery->where('name', 'LIKE', "%{$searchTerm}%")
                                ->orWhere('email', 'LIKE', "%{$searchTerm}%");
                        })
                        ->orWhereHas('detailTransactions.product', function ($productQuery) use ($searchTerm) {
                            $productQuery->where('name', 'LIKE', "%{$searchTerm}%");
                        })
                        ->orWhereHas('detailTransactions.bundling', function ($bundlingQuery) use ($searchTerm) {
                            $bundlingQuery->where('name', 'LIKE', "%{$searchTerm}%");
                        })
                        ->orWhereHas('detailTransactions.productItems', function ($serialQuery) use ($searchTerm) {
                            $serialQuery->where('serial_number', 'LIKE', "%{$searchTerm}%");
                        });
                })
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        }, 10); // Cache for 10 minutes due to frequently changing data
    }
    
    /**
     * Get transactions by status with caching
     */
    public function getByStatus(array $statuses, int $limit = 100): Collection
    {
        $cacheKey = "transactions_by_status_" . md5(implode(',', $statuses)) . "_{$limit}";
        
        return ResourceCacheService::cacheComplexQuery($cacheKey, function () use ($statuses, $limit) {
            return $this->model->query()
                ->select([
                    'id',
                    'booking_transaction_id',
                    'customer_id',
                    'booking_status',
                    'start_date',
                    'end_date',
                    'grand_total'
                ])
                ->with(['customer:id,name'])
                ->whereIn('booking_status', $statuses)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Get recent transactions with optimized query
     */
    public function getRecent(int $days = 30, int $limit = 50): Collection
    {
        $cacheKey = "recent_transactions_{$days}_{$limit}";
        
        return ResourceCacheService::cacheComplexQuery($cacheKey, function () use ($days, $limit) {
            return $this->model->query()
                ->select([
                    'id',
                    'booking_transaction_id',
                    'customer_id',
                    'booking_status',
                    'start_date',
                    'end_date',
                    'grand_total',
                    'created_at'
                ])
                ->with(['customer:id,name,email'])
                ->where('created_at', '>=', now()->subDays($days))
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Get transaction statistics
     */
    public function getStatistics(int $days = 30): array
    {
        $cacheKey = "transaction_stats_{$days}";
        
        return ResourceCacheService::cacheComplexQuery($cacheKey, function () use ($days) {
            $startDate = now()->subDays($days);
            
            return [
                'total' => $this->model->where('created_at', '>=', $startDate)->count(),
                'by_status' => $this->model
                    ->select('booking_status', \DB::raw('COUNT(*) as count'))
                    ->where('created_at', '>=', $startDate)
                    ->groupBy('booking_status')
                    ->pluck('count', 'booking_status')
                    ->toArray(),
                'total_revenue' => $this->model
                    ->where('created_at', '>=', $startDate)
                    ->where('booking_status', '!=', 'cancel')
                    ->sum('grand_total'),
                'average_order_value' => $this->model
                    ->where('created_at', '>=', $startDate)
                    ->where('booking_status', '!=', 'cancel')
                    ->avg('grand_total'),
            ];
        }, 30); // Cache for 30 minutes
    }
    
    /**
     * Get active rentals for date range
     */
    public function getActiveRentals(Carbon $startDate, Carbon $endDate): Collection
    {
        $cacheKey = "active_rentals_" . md5($startDate->toDateString() . $endDate->toDateString());
        
        return ResourceCacheService::cacheComplexQuery($cacheKey, function () use ($startDate, $endDate) {
            return $this->model->query()
                ->select([
                    'id',
                    'booking_transaction_id',
                    'customer_id',
                    'start_date',
                    'end_date',
                    'booking_status'
                ])
                ->with(['detailTransactions.productItems:id,serial_number,product_id'])
                ->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->get();
        }, 5); // Cache for 5 minutes due to frequently changing rental status
    }
    
    /**
     * Get paginated transactions with filters
     */
    public function getPaginatedWithFilters(array $filters, int $perPage = 25): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = $this->model->query()
            ->select([
                'transactions.id',
                'transactions.booking_transaction_id',
                'transactions.customer_id',
                'transactions.start_date',
                'transactions.end_date',
                'transactions.grand_total',
                'transactions.booking_status',
                'transactions.promo_id',
                'transactions.created_at'
            ])
            ->with($this->getEagerLoadRelations());
            
        // Apply filters
        if (!empty($filters['status'])) {
            $query->whereIn('booking_status', (array) $filters['status']);
        }
        
        if (!empty($filters['date_range'])) {
            $dateRange = $filters['date_range'];
            if (isset($dateRange['start']) && isset($dateRange['end'])) {
                $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
            }
        }
        
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('booking_transaction_id', 'LIKE', "%{$searchTerm}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                        $customerQuery->where('name', 'LIKE', "%{$searchTerm}%");
                    });
            });
        }
        
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
    
    /**
     * Bulk update transaction status
     */
    public function bulkUpdateStatus(array $transactionIds, string $status): int
    {
        $updated = $this->model->whereIn('id', $transactionIds)
            ->update(['booking_status' => $status, 'updated_at' => now()]);
            
        // Clear related caches
        $this->clearModelCache();
        ResourceCacheService::invalidateByPattern('transaction_*');
        
        return $updated;
    }
    
    /**
     * Get transactions that need status update (overdue, etc.)
     */
    public function getTransactionsNeedingStatusUpdate(): Collection
    {
        $cacheKey = 'transactions_needing_status_update';
        
        return ResourceCacheService::cacheComplexQuery($cacheKey, function () {
            $now = now();
            
            return $this->model->query()
                ->select(['id', 'booking_transaction_id', 'booking_status', 'start_date', 'end_date'])
                ->where(function ($query) use ($now) {
                    // Rentals that should be active but are still 'paid'
                    $query->where('booking_status', 'paid')
                        ->where('start_date', '<=', $now)
                        ->where('end_date', '>=', $now);
                })
                ->orWhere(function ($query) use ($now) {
                    // Rentals that should be completed
                    $query->whereIn('booking_status', ['paid', 'on_rented'])
                        ->where('end_date', '<', $now);
                })
                ->limit(100)
                ->get();
        }, 2); // Cache for 2 minutes
    }
}
