<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Bundling;
use App\Models\ProductItem;
use App\Models\DetailTransactionProductItem;

/**
 * Virtual model for Product Availability functionality
 * This prevents Product::class duplication in Filament Shield
 */
class ProductAvailability extends Model
{
    // This is a virtual model that extends Product functionality
    // for availability checking without duplicating Product::class in Shield
    
    protected $fillable = [
        'id',
        'name', 
        'type',
        'product_model',
        'bundling_model',
        'total_items',
        'available_items'
    ];
    
    // Disable timestamps since this is a virtual model
    public $timestamps = false;
    
    protected $casts = [
        'product_model' => 'object',
        'bundling_model' => 'object'
    ];

    /**
     * Get all products and bundlings with availability data
     */
    public static function getAllAvailabilityData(?string $searchTerm = null): Collection
    {
        $items = collect();
        
        // Get products with availability info
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
            $item->product_model = $product;
            return $item;
        });
        
        // Get bundlings with availability info
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
            $item->bundling_model = $bundling;
            return $item;
        });
        
        return $products->merge($bundlings);
    }
    
    /**
     * Get availability data for selected products and bundlings only
     */
    public static function getSelectedAvailabilityData($searchTerm = null, $selectedProducts = [], $selectedBundlings = [])
    {
        $items = collect();
        
        // Get selected products
        if (!empty($selectedProducts)) {
            $productsQuery = Product::whereIn('id', $selectedProducts)->with(['items', 'bundlings']);
            
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
                $item->product_model = $product;
                return $item;
            });
            
            $items = $items->merge($products);
        }
        
        // Get selected bundlings
        if (!empty($selectedBundlings)) {
            $bundlingsQuery = Bundling::whereIn('id', $selectedBundlings)->with(['products.items']);
            
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
                $item->bundling_model = $bundling;
                return $item;
            });
            
            $items = $items->merge($bundlings);
        }
        
        return $items;
    }

    /**
     * Get total items for availability calculation
     */
    public function getTotalItems()
    {
        try {
            if ($this->type === 'product' && $this->product_model) {
                // For product: count serial numbers from product_items table
                if (isset($this->product_model->id)) {
                    return \App\Models\ProductItem::where('product_id', $this->product_model->id)->count();
                }
                return 0;
            } elseif ($this->type === 'bundling' && $this->bundling_model) {
                // For bundling: calculate maximum possible bundles based on products
                if (isset($this->bundling_model->id)) {
                    return $this->getTotalBundlingItems($this->bundling_model->id);
                }
                return 0;
            }
        } catch (\Exception $e) {
            \Log::error('Error in getTotalItems: ' . $e->getMessage());
            return 0;
        }
        return 0;
    }

    /**
     * Get available items for a specific date range
     */
    public function getAvailableItemsForPeriod($startDate, $endDate)
    {
        try {
            if ($this->type === 'product' && $this->product_model) {
                // For product: Total items - items in transactions
                if (isset($this->product_model->id)) {
                    return $this->getAvailableProductItems($this->product_model->id, $startDate, $endDate);
                }
                return 0;
            } elseif ($this->type === 'bundling' && $this->bundling_model) {
                // For bundling: Total quantity - quantity in transactions
                if (isset($this->bundling_model->id)) {
                    return $this->getAvailableBundlingItems($this->bundling_model->id, $startDate, $endDate);
                }
                return 0;
            }
        } catch (\Exception $e) {
            \Log::error('Error in getAvailableItemsForPeriod: ' . $e->getMessage());
            return 0;
        }
        return 0;
    }

    /**
     * Calculate available product items for date range
     */
    private function getAvailableProductItems($productId, $startDate, $endDate): int
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } catch (\Exception $e) {
            return 0;
        }

        $totalItems = ProductItem::where('product_id', $productId)
            ->where('is_available', true)
            ->count();

        // Get unique product items that are used in transactions during the period
        $usedItemIds = DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) use ($start, $end) {
            $query->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                ->where(function ($q) use ($start, $end) {
                    // Check for overlapping rental periods
                    $q->where(function ($overlap) use ($start, $end) {
                        $overlap->whereBetween('start_date', [$start, $end])
                            ->orWhereBetween('end_date', [$start, $end])
                            ->orWhere(function ($contains) use ($start, $end) {
                                $contains->where('start_date', '<=', $start)
                                    ->where('end_date', '>=', $end);
                            });
                    });
                });
        })
        ->whereHas('productItem', function ($query) use ($productId) {
            $query->where('product_id', $productId)
                ->where('is_available', true);
        })
        ->distinct()
        ->pluck('product_item_id')
        ->unique()
        ->count();

        return max(0, $totalItems - $usedItemIds);
    }
    
    /**
     * Get total bundling items (maximum possible bundles based on products)
     */
    private function getTotalBundlingItems($bundlingId): int
    {
        try {
            $bundling = \App\Models\Bundling::with(['products'])->find($bundlingId);
            if (!$bundling || $bundling->products->isEmpty()) {
                return 0;
            }

            $minQuantity = null;
            
            foreach ($bundling->products as $product) {
                $requiredPerBundle = $product->pivot->quantity ?? 1;
                $availableItems = \App\Models\ProductItem::where('product_id', $product->id)->count();
                
                // How many bundles can be made from this product
                $possibleFromThisProduct = intval($availableItems / $requiredPerBundle);
                
                $minQuantity = is_null($minQuantity) 
                    ? $possibleFromThisProduct 
                    : min($minQuantity, $possibleFromThisProduct);
            }
            
            return $minQuantity ?? 0;
        } catch (\Exception $e) {
            \Log::error('Error in getTotalBundlingItems: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calculate available bundling items for date range
     */
    private function getAvailableBundlingItems($bundlingId, $startDate, $endDate): int
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
        } catch (\Exception $e) {
            return 0;
        }

        $bundling = \App\Models\Bundling::with(['products'])->find($bundlingId);
        if (!$bundling || $bundling->products->isEmpty()) {
            return 0;
        }

        $minAvailable = null;
        
        foreach ($bundling->products as $product) {
            $requiredPerBundle = $product->pivot->quantity ?? 1;
            
            // Get total items for this product
            $totalItems = \App\Models\ProductItem::where('product_id', $product->id)->count();
            
            // Get used items for this product in the date range
            $usedItems = \App\Models\DetailTransactionProductItem::whereHas('detailTransaction.transaction', function ($query) use ($start, $end) {
                $query->whereIn('booking_status', ['booking', 'paid', 'on_rented'])
                    ->where(function ($q) use ($start, $end) {
                        $q->whereBetween('start_date', [$start, $end])
                            ->orWhereBetween('end_date', [$start, $end])
                            ->orWhere(function ($q2) use ($start, $end) {
                                $q2->where('start_date', '<=', $start)
                                    ->where('end_date', '>=', $end);
                            });
                    });
            })->whereHas('productItem', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })->count();
            
            $availableItems = max(0, $totalItems - $usedItems);
            
            // How many bundles can be made from available items of this product
            $possibleFromThisProduct = intval($availableItems / $requiredPerBundle);
            
            $minAvailable = is_null($minAvailable) 
                ? $possibleFromThisProduct 
                : min($minAvailable, $possibleFromThisProduct);
        }

        return $minAvailable ?? 0;
    }

    /**
     * Get description for the availability item
     */
    public function getDescription()
    {
        if ($this->type === 'product' && $this->product_model) {
            // Handle product bundlings with safety checks
            $bundlings = $this->product_model->bundlings ?? null;
            if ($bundlings && is_countable($bundlings) && count($bundlings) > 0) {
                // Ensure we have a collection
                $bundlings = collect($bundlings);
                $bundlingNames = $bundlings->pluck('name')->take(3)->implode(', ');
                $remaining = $bundlings->count() - 3;
                $suffix = $remaining > 0 ? " (+{$remaining} more)" : '';
                return "Used in bundles: {$bundlingNames}{$suffix}";
            }
            return $this->product_model->description ?? null;
        } elseif ($this->type === 'bundling' && $this->bundling_model) {
            // Handle bundling products with safety checks
            $products = $this->bundling_model->products ?? null;
            if ($products && is_countable($products) && count($products) > 0) {
                // Ensure we have a collection
                $products = collect($products);
                $productNames = $products->pluck('name')->take(3)->implode(', ');
                $remaining = $products->count() - 3;
                $suffix = $remaining > 0 ? " (+{$remaining} more)" : '';
                return "Contains products: {$productNames}{$suffix}";
            }
            return $this->bundling_model->description ?? null;
        }
        return null;
    }

    /**
     * Get active rental periods for this item
     */
    public function getActiveRentalPeriods($startDate, $endDate)
    {
        try {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            
            if ($this->type === 'product' && $this->product_model) {
                return $this->getProductRentalPeriods($this->product_model->id, $start, $end);
            } elseif ($this->type === 'bundling' && $this->bundling_model) {
                return $this->getBundlingRentalPeriods($this->bundling_model->id, $start, $end);
            }
        } catch (\Exception $e) {
            \Log::error('Error getting rental periods: ' . $e->getMessage());
        }
        
        return 'No active rentals';
    }
    
    /**
     * Get product rental periods
     */
    private function getProductRentalPeriods($productId, $start, $end)
    {
        // Get transactions for this product with booking, paid, or on_rented status
        // that overlap with the selected date range or are currently active
        $transactions = \App\Models\Transaction::whereIn('booking_status', ['booking', 'paid', 'on_rented'])
            ->whereHas('detailTransactions', function ($query) use ($productId) {
                $query->whereHas('detailTransactionProductItems.productItem', function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                });
            })
            ->where(function ($query) use ($start, $end) {
                // Include transactions that overlap with the selected period
                $query->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start, $end])
                        ->orWhereBetween('end_date', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('start_date', '<=', $start)
                                ->where('end_date', '>=', $end);
                        });
                })
                // Also include currently active transactions (not yet ended)
                ->orWhere(function ($q) {
                    $q->where('end_date', '>=', now())
                        ->where('start_date', '<=', now());
                });
            })
            ->with(['customer'])
            ->orderBy('start_date', 'desc')
            ->get();
            
        return $this->formatRentalPeriods($transactions);
    }
    
    /**
     * Get bundling rental periods
     */
    private function getBundlingRentalPeriods($bundlingId, $start, $end)
    {
        // Get transactions for this bundling with booking, paid, or on_rented status
        // that overlap with the selected date range or are currently active
        $transactions = \App\Models\Transaction::whereIn('booking_status', ['booking', 'paid', 'on_rented'])
            ->whereHas('detailTransactions', function ($query) use ($bundlingId) {
                $query->where('bundling_id', $bundlingId);
            })
            ->where(function ($query) use ($start, $end) {
                // Include transactions that overlap with the selected period
                $query->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start, $end])
                        ->orWhereBetween('end_date', [$start, $end])
                        ->orWhere(function ($q2) use ($start, $end) {
                            $q2->where('start_date', '<=', $start)
                                ->where('end_date', '>=', $end);
                        });
                })
                // Also include currently active transactions (not yet ended)
                ->orWhere(function ($q) {
                    $q->where('end_date', '>=', now())
                        ->where('start_date', '<=', now());
                });
            })
            ->with(['customer'])
            ->orderBy('start_date', 'desc')
            ->get();
            
        return $this->formatRentalPeriods($transactions);
    }
    
    /**
     * Format rental periods for display
     */
    private function formatRentalPeriods($transactions)
    {
        if ($transactions->isEmpty()) {
            return '<span class="text-gray-500 text-sm">No active rentals</span>';
        }
        
        if ($transactions->count() == 1) {
            // Single transaction - show direct edit link
            $transaction = $transactions->first();
            $startDate = Carbon::parse($transaction->start_date)->format('M d');
            $endDate = Carbon::parse($transaction->end_date)->format('M d, Y');
            $customerName = $transaction->customer->name ?? 'Unknown';
            $status = ucfirst($transaction->booking_status);
            
            $editUrl = route('filament.admin.resources.transactions.edit', ['record' => $transaction->id]);
            
            return '<div class="text-sm">'.
                   '<a href="'.$editUrl.'" class="text-primary-600 hover:text-primary-800 font-medium" target="_blank">'.
                   '🔗 '.$customerName.'</a><br>'.
                   '<span class="text-xs text-gray-600">'.$startDate.' - '.$endDate.'</span><br>'.
                   '<span class="text-xs px-1 py-0.5 rounded bg-blue-100 text-blue-800">'.$status.'</span>'.
                   '</div>';
        } else {
            // Multiple transactions - show each with individual edit links
            $periods = [];
            foreach ($transactions as $index => $transaction) {
                $startDate = Carbon::parse($transaction->start_date)->format('M d');
                $endDate = Carbon::parse($transaction->end_date)->format('M d, Y');
                $customerName = $transaction->customer->name ?? 'Unknown';
                $status = ucfirst($transaction->booking_status);
                $editUrl = route('filament.admin.resources.transactions.edit', ['record' => $transaction->id]);
                
                $periods[] = '<div class="mb-2 p-2 border-l-2 border-gray-200 text-xs">'.
                            '<a href="'.$editUrl.'" class="text-primary-600 hover:text-primary-800 font-medium" target="_blank">'.
                            '🔗 '.$customerName.'</a><br>'.
                            '<span class="text-gray-600">'.$startDate.' - '.$endDate.'</span><br>'.
                            '<span class="px-1 py-0.5 rounded bg-blue-100 text-blue-800">'.$status.'</span>'.
                            '</div>';
            }
            
            $totalCount = $transactions->count();
            return '<div class="text-sm">'.
                   '<div class="font-medium text-gray-700 mb-1">'.$totalCount.' Active Transactions:</div>'.
                   '<div class="max-h-40 overflow-y-auto space-y-1">'.implode('', array_slice($periods, 0, 5)).'</div>'.
                   ($totalCount > 5 ? '<div class="text-xs text-gray-500 mt-1">+'.($totalCount-5).' more transactions...</div>' : '').
                   '</div>';
        }
    }
}
