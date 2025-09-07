<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\Bundling;
use App\Models\DetailTransaction;
use App\Models\DetailTransactionProductItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AvailabilityService
{
    /**
     * Check availability for multiple items and date ranges
     */
    public function checkMultipleAvailability(array $checks): array
    {
        $results = [];
        
        foreach ($checks as $check) {
            $key = "{$check['type']}-{$check['id']}-{$check['start_date']}-{$check['end_date']}";
            $results[$key] = $this->checkAvailability(
                $check['type'],
                $check['id'],
                Carbon::parse($check['start_date']),
                Carbon::parse($check['end_date']),
                $check['quantity'] ?? 1
            );
        }

        return $results;
    }

    /**
     * Check availability for single item
     */
    public function checkAvailability(
        string $type, 
        int $id, 
        Carbon $startDate, 
        Carbon $endDate, 
        int $quantity = 1
    ): array {
        try {
            // Validate input
            if (!in_array($type, ['product', 'bundling'])) {
                throw new \InvalidArgumentException('Invalid type. Must be product or bundling.');
            }

            if ($startDate->gte($endDate)) {
                throw new \InvalidArgumentException('Start date must be before end date.');
            }

            if ($quantity < 1) {
                throw new \InvalidArgumentException('Quantity must be at least 1.');
            }

            // Check if item exists and is available
            $item = $this->getItem($type, $id);
            if (!$item || $item->status !== 'available') {
                return [
                    'available' => false,
                    'available_quantity' => 0,
                    'conflicting_transactions' => [],
                    'unavailable_dates' => [],
                    'message' => 'Item not found or not available',
                ];
            }

            // Get conflicting transactions
            $conflictingTransactions = $this->getConflictingTransactions($type, $id, $startDate, $endDate);
            
            // Calculate used quantity
            $usedQuantity = $this->calculateUsedQuantity($conflictingTransactions, $type, $id);
            
            // Get total stock (for now assume 10, should come from inventory system)
            $totalStock = $this->getTotalStock($type, $id);
            
            // Calculate available quantity
            $availableQuantity = max(0, $totalStock - $usedQuantity);
            $isAvailable = $availableQuantity >= $quantity;

            // Get unavailable dates
            $unavailableDates = $this->getUnavailableDates($type, $id, $startDate, $endDate);

            return [
                'available' => $isAvailable,
                'available_quantity' => $availableQuantity,
                'total_stock' => $totalStock,
                'used_quantity' => $usedQuantity,
                'requested_quantity' => $quantity,
                'conflicting_transactions' => $this->formatConflictingTransactions($conflictingTransactions),
                'unavailable_dates' => $unavailableDates,
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                    'duration' => $startDate->diffInDays($endDate) + 1,
                ],
                'item' => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => $type,
                    'status' => $item->status,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('Availability check failed', [
                'type' => $type,
                'id' => $id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);

            return [
                'available' => false,
                'available_quantity' => 0,
                'conflicting_transactions' => [],
                'unavailable_dates' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get unavailable dates for calendar display
     */
    public function getUnavailableDates(string $type, int $id, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        try {
            $query = Transaction::query()
                ->whereIn('booking_status', ['confirmed', 'active', 'processing'])
                ->whereHas('detailTransactions', function ($q) use ($type, $id) {
                    if ($type === 'product') {
                        $q->where('product_id', $id);
                    } else {
                        $q->where('bundling_id', $id);
                    }
                });

            // If date range specified, get overlapping transactions
            if ($startDate && $endDate) {
                $query->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->where('start_date', '<=', $startDate)
                             ->where('end_date', '>=', $endDate);
                      });
                });
            }

            $transactions = $query->get(['start_date', 'end_date']);
            
            $unavailableDates = [];
            foreach ($transactions as $transaction) {
                $start = Carbon::parse($transaction->start_date);
                $end = Carbon::parse($transaction->end_date);
                
                while ($start->lte($end)) {
                    $unavailableDates[] = $start->toDateString();
                    $start->addDay();
                }
            }

            return array_unique($unavailableDates);

        } catch (\Exception $e) {
            Log::error('Get unavailable dates failed', [
                'type' => $type,
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Check if specific date range is available
     */
    public function isDateRangeAvailable(string $type, int $id, Carbon $startDate, Carbon $endDate): bool
    {
        $result = $this->checkAvailability($type, $id, $startDate, $endDate, 1);
        return $result['available'] ?? false;
    }

    /**
     * Get bulk availability for multiple items (for cart validation)
     */
    public function checkCartAvailability(array $cartItems): array
    {
        $results = [];
        $allAvailable = true;

        foreach ($cartItems as $item) {
            $startDate = Carbon::parse($item['start_date']);
            $endDate = Carbon::parse($item['end_date']);
            
            $result = $this->checkAvailability(
                $item['type'],
                $item['id'],
                $startDate,
                $endDate,
                $item['quantity']
            );

            $results[] = array_merge($result, [
                'cart_item_id' => $item['cart_item_id'] ?? null,
                'name' => $item['name'] ?? null,
            ]);

            if (!$result['available']) {
                $allAvailable = false;
            }
        }

        return [
            'all_available' => $allAvailable,
            'items' => $results,
            'summary' => [
                'total_items' => count($cartItems),
                'available_items' => count(array_filter($results, fn($r) => $r['available'])),
                'unavailable_items' => count(array_filter($results, fn($r) => !$r['available'])),
            ]
        ];
    }

    /**
     * Get item (product or bundling)
     */
    private function getItem(string $type, int $id)
    {
        if ($type === 'product') {
            return Product::find($id);
        } else {
            return Bundling::find($id);
        }
    }

    /**
     * Get conflicting transactions for date range
     */
    private function getConflictingTransactions(string $type, int $id, Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::query()
            ->whereIn('booking_status', ['confirmed', 'active', 'processing'])
            ->where(function ($q) use ($startDate, $endDate) {
                // Check for overlapping date ranges
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            })
            ->whereHas('detailTransactions', function ($q) use ($type, $id) {
                if ($type === 'product') {
                    $q->where('product_id', $id);
                } else {
                    $q->where('bundling_id', $id);
                }
            })
            ->with(['detailTransactions' => function ($q) use ($type, $id) {
                if ($type === 'product') {
                    $q->where('product_id', $id);
                } else {
                    $q->where('bundling_id', $id);
                }
            }])
            ->get();
    }

    /**
     * Calculate total used quantity from conflicting transactions
     */
    private function calculateUsedQuantity(Collection $transactions, string $type, int $id): int
    {
        $totalUsed = 0;

        foreach ($transactions as $transaction) {
            foreach ($transaction->detailTransactions as $detail) {
                if ($type === 'product' && $detail->product_id == $id) {
                    $totalUsed += $detail->quantity;
                } elseif ($type === 'bundling' && $detail->bundling_id == $id) {
                    $totalUsed += $detail->quantity;
                }
            }
        }

        return $totalUsed;
    }

    /**
     * Get total stock for item (should be replaced with actual inventory system)
     */
    private function getTotalStock(string $type, int $id): int
    {
        // For now, return a default stock level
        // In production, this should come from an inventory system
        if ($type === 'product') {
            // Count product items if available
            $productItemsCount = DB::table('product_items')
                ->where('product_id', $id)
                ->count();
            
            return $productItemsCount > 0 ? $productItemsCount : 10;
        } else {
            // For bundlings, assume default stock
            return 5;
        }
    }

    /**
     * Format conflicting transactions for response
     */
    private function formatConflictingTransactions(Collection $transactions): array
    {
        return $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'booking_transaction_id' => $transaction->booking_transaction_id,
                'start_date' => $transaction->start_date,
                'end_date' => $transaction->end_date,
                'booking_status' => $transaction->booking_status,
                'customer_name' => $transaction->customer?->name ?? 'Unknown',
                'details_count' => $transaction->detailTransactions->count(),
            ];
        })->toArray();
    }

    /**
     * Get availability statistics
     */
    public function getAvailabilityStats(): array
    {
        try {
            $stats = [
                'products' => [
                    'total' => Product::count(),
                    'available' => Product::where('status', 'available')->count(),
                    'unavailable' => Product::where('status', '!=', 'available')->count(),
                ],
                'bundlings' => [
                    'total' => Bundling::count(),
                    'available' => Bundling::where('status', 'available')->count(),
                    'unavailable' => Bundling::where('status', '!=', 'available')->count(),
                ],
                'active_transactions' => Transaction::whereIn('booking_status', ['confirmed', 'active', 'processing'])->count(),
                'period' => [
                    'from' => now()->toDateString(),
                    'to' => now()->addDays(30)->toDateString(),
                ]
            ];

            return $stats;

        } catch (\Exception $e) {
            Log::error('Get availability stats failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
