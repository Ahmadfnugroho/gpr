<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventoryAvailability extends Model
{
    // This is a virtual model for combining product and bundling availability
    // It doesn't have a real database table
    
    protected $fillable = [
        'id',
        'name',
        'type', // 'product' or 'bundling'
        'original_model', // The actual Product or Bundling model
        'total_items',
        'available_items',
        'availability_percentage'
    ];
    
    // Disable timestamps since this is a virtual model
    public $timestamps = false;
    
    protected $casts = [
        'original_model' => 'object'
    ];

    /**
     * Get all inventory items (both products and bundlings)
     */
    public static function getAllInventoryItems(?string $searchTerm = null): Collection
    {
        $items = collect();
        
        // Get products
        $productsQuery = Product::with(['items', 'bundlings']);
        if ($searchTerm && strlen(trim($searchTerm)) >= 2) {
            $keywords = array_filter(array_map('trim', explode(' ', strtolower($searchTerm))));
            if (!empty($keywords)) {
                $productsQuery->where(function ($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $q->whereRaw('LOWER(name) LIKE ?', ["%{$keyword}%"]);
                    }
                });
            }
        }
        
        $products = $productsQuery->get()->map(function ($product) {
            $item = new static();
            $item->id = 'product_' . $product->id;
            $item->name = $product->name;
            $item->type = 'product';
            $item->original_model = $product;
            return $item;
        });
        
        // Get bundlings
        $bundlingsQuery = Bundling::with(['products.items']);
        if ($searchTerm && strlen(trim($searchTerm)) >= 2) {
            $keywords = array_filter(array_map('trim', explode(' ', strtolower($searchTerm))));
            if (!empty($keywords)) {
                $bundlingsQuery->where(function ($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $q->whereRaw('LOWER(name) LIKE ?', ["%{$keyword}%"]);
                    }
                });
            }
        }
        
        $bundlings = $bundlingsQuery->get()->map(function ($bundling) {
            $item = new static();
            $item->id = 'bundling_' . $bundling->id;
            $item->name = $bundling->name . ' (Bundle)';
            $item->type = 'bundling';
            $item->original_model = $bundling;
            return $item;
        });
        
        return $products->merge($bundlings);
    }

    /**
     * Get the total items count
     */
    public function getTotalItemsAttribute()
    {
        if ($this->type === 'product') {
            return $this->original_model->items()->count();
        } elseif ($this->type === 'bundling') {
            // Calculate minimum bundles possible
            $minAvailable = null;
            foreach ($this->original_model->products as $product) {
                $requiredPerBundle = $product->pivot->quantity ?? 1;
                $totalItems = $product->items()->count();
                $maxBundlesFromThisProduct = intdiv($totalItems, $requiredPerBundle);
                
                $minAvailable = is_null($minAvailable) 
                    ? $maxBundlesFromThisProduct 
                    : min($minAvailable, $maxBundlesFromThisProduct);
            }
            return $minAvailable ?? 0;
        }
        return 0;
    }

    /**
     * Get available items count for a date range
     */
    public function getAvailableItemsForPeriod($startDate, $endDate)
    {
        if ($this->type === 'product') {
            return $this->getAvailableProductItems($this->original_model->id, $startDate, $endDate);
        } elseif ($this->type === 'bundling') {
            return $this->original_model->getAvailableQuantityForPeriod(
                Carbon::parse($startDate),
                Carbon::parse($endDate)
            );
        }
        return 0;
    }

    /**
     * Get available product items count
     */
    private function getAvailableProductItems($productId, $startDate, $endDate): int
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } catch (\Exception $e) {
            return 0;
        }

        $totalItems = ProductItem::where('product_id', $productId)->count();

        $usedItems = DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) use ($start, $end) {
            $query->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start, $end])
                        ->orWhereBetween('end_date', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('start_date', '<=', $start)
                                ->where('end_date', '>=', $end);
                        });
                });
        })->whereHas('productItem', function ($query) use ($productId) {
            $query->where('product_id', $productId);
        })->count();

        return max(0, $totalItems - $usedItems);
    }

    /**
     * Get current rentals count
     */
    public function getCurrentRentalsForPeriod($startDate, $endDate)
    {
        if ($this->type === 'product') {
            return DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) use ($startDate, $endDate) {
                $query->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date', [$startDate, $endDate])
                            ->orWhereBetween('end_date', [$startDate, $endDate])
                            ->orWhere(function ($q2) use ($startDate, $endDate) {
                                $q2->where('start_date', '<=', $startDate)
                                    ->where('end_date', '>=', $endDate);
                            });
                    });
            })->whereHas('productItem', function ($query) {
                $query->where('product_id', $this->original_model->id);
            })->count();
        } elseif ($this->type === 'bundling') {
            return $this->original_model->detailTransactions()
                ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                    $query->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                        ->where(function ($q) use ($startDate, $endDate) {
                            $q->whereBetween('start_date', [$startDate, $endDate])
                                ->orWhereBetween('end_date', [$startDate, $endDate])
                                ->orWhere(function ($q2) use ($startDate, $endDate) {
                                    $q2->where('start_date', '<=', $startDate)
                                        ->where('end_date', '>=', $endDate);
                                });
                        });
                })
                ->sum('quantity');
        }
        return 0;
    }

    /**
     * Get description for the item
     */
    public function getDescription()
    {
        if ($this->type === 'product') {
            $bundlings = $this->original_model->bundlings ?? collect();
            if ($bundlings->isNotEmpty()) {
                $bundlingNames = $bundlings->pluck('name')->take(3)->implode(', ');
                $remaining = $bundlings->count() - 3;
                $suffix = $remaining > 0 ? " (+{$remaining} more)" : '';
                return "Used in bundles: {$bundlingNames}{$suffix}";
            }
            return null;
        } elseif ($this->type === 'bundling') {
            $productNames = $this->original_model->products->pluck('name')->take(3)->implode(', ');
            $remaining = $this->original_model->products->count() - 3;
            $suffix = $remaining > 0 ? " (+{$remaining} more)" : '';
            return "Contains: {$productNames}{$suffix}";
        }
        return null;
    }
}
