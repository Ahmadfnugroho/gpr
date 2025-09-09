<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\Customer;
use App\Models\DetailTransaction;
use App\Models\ProductItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TransactionService
{
    /**
     * Create a new transaction with proper validation and business logic
     */
    public function createTransaction(array $data): Transaction
    {
        return DB::transaction(function () use ($data) {
            // Validate customer exists and is active
            $customer = Customer::find($data['customer_id']);
            if (!$customer || $customer->status !== 'active') {
                throw new \Exception('Customer not found or inactive');
            }

            // Validate date range
            $startDate = Carbon::parse($data['start_date']);
            $endDate = Carbon::parse($data['end_date']);
            
            if ($startDate->isPast()) {
                throw new \Exception('Start date cannot be in the past');
            }

            // Create transaction
            $transaction = Transaction::create([
                'customer_id' => $data['customer_id'],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration' => $data['duration'],
                'booking_status' => 'booking',
                'note' => $data['note'] ?? null,
                'promo_id' => $data['promo_id'] ?? null,
            ]);

            // Create detail transactions
            $grandTotal = 0;
            foreach ($data['detail_transactions'] as $detailData) {
                $grandTotal += $this->createDetailTransaction($transaction, $detailData, $startDate, $endDate);
            }

            // Update grand total and payment
            $transaction->update([
                'grand_total' => $grandTotal,
                'down_payment' => $data['down_payment'] ?? (int)($grandTotal * 0.5),
                'remaining_payment' => $grandTotal - ($data['down_payment'] ?? (int)($grandTotal * 0.5)),
            ]);

            // Clear related caches
            $this->clearTransactionCaches($transaction);

            return $transaction->fresh();
        });
    }

    /**
     * Create detail transaction with product availability validation
     */
    protected function createDetailTransaction(Transaction $transaction, array $data, Carbon $startDate, Carbon $endDate): int
    {
        if (isset($data['product_id'])) {
            return $this->createProductDetailTransaction($transaction, $data, $startDate, $endDate);
        } elseif (isset($data['bundling_id'])) {
            return $this->createBundlingDetailTransaction($transaction, $data, $startDate, $endDate);
        }

        throw new \Exception('Either product_id or bundling_id must be provided');
    }

    /**
     * Create detail transaction for single product
     */
    protected function createProductDetailTransaction(Transaction $transaction, array $data, Carbon $startDate, Carbon $endDate): int
    {
        $product = Product::find($data['product_id']);
        if (!$product) {
            throw new \Exception('Product not found');
        }

        // Check availability
        $availableQuantity = $product->getAvailableQuantityForPeriod($startDate, $endDate);
        if ($availableQuantity < $data['quantity']) {
            throw new \Exception("Insufficient product availability. Available: {$availableQuantity}, Requested: {$data['quantity']}");
        }

        // Get available serial numbers
        $availableSerials = $product->getAvailableSerialNumbersForPeriod($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
        $requiredSerials = array_slice($availableSerials, 0, $data['quantity']);

        if (count($requiredSerials) < $data['quantity']) {
            throw new \Exception('Insufficient serial numbers available');
        }

        // Create detail transaction
        $detailTransaction = DetailTransaction::create([
            'transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'quantity' => $data['quantity'],
            'price' => $product->price,
            'total_price' => $product->price * $data['quantity'],
        ]);

        // Assign serial numbers
        $productItemIds = ProductItem::whereIn('serial_number', $requiredSerials)->pluck('id');
        \Illuminate\Support\Facades\Log::info('ProductItemIds for sync (single product)', ['product_item_ids' => $productItemIds->toArray()]);
        $detailTransaction->productItems()->sync($productItemIds);
        \Illuminate\Support\Facades\Log::info('ProductItemIds after sync (single product)', ['detail_transaction_id' => $detailTransaction->id, 'synced_product_item_ids' => $detailTransaction->productItems->pluck('id')->toArray()]);

        return $detailTransaction->total_price;
    }

    /**
     * Create detail transaction for bundling
     */
    protected function createBundlingDetailTransaction(Transaction $transaction, array $data, Carbon $startDate, Carbon $endDate): int
    {
        Log::info('createBundlingDetailTransaction method entered.');
        $bundling = \App\Models\Bundling::with('products')->find($data['bundling_id']);
        if (!$bundling) {
            throw new \Exception('Bundling not found');
        }

        // Validate bundling availability
        $availableQuantity = $bundling->getAvailableQuantityForPeriod($startDate, $endDate, $data['quantity']);
        if ($availableQuantity <= 0) {
            throw new \Exception('Bundling not available for the selected period');
        }

        // Create detail transaction
        $detailTransaction = DetailTransaction::create([
            'transaction_id' => $transaction->id,
            'bundling_id' => $bundling->id,
            'quantity' => $data['quantity'],
            'price' => $bundling->price,
            'total_price' => $bundling->price * $data['quantity'],
        ]);

        // Assign product items for bundling
        $assignedItemIds = $this->assignBundlingProductItems($bundling, $data['quantity'], $startDate, $endDate);
        
        \Illuminate\Support\Facades\Log::info('Assigned ProductItemIds for bundling', ['detail_transaction_id' => $detailTransaction->id, 'assigned_item_ids' => $assignedItemIds]);

        $detailTransaction->productItems()->sync($assignedItemIds);
        \Illuminate\Support\Facades\Log::info('ProductItemIds after sync (bundling)', ['detail_transaction_id' => $detailTransaction->id, 'synced_product_item_ids' => $detailTransaction->productItems->pluck('id')->toArray()]);

        return $detailTransaction->total_price;
    }

    /**
     * Assign product items for bundling
     */
    protected function assignBundlingProductItems(\App\Models\Bundling $bundling, int $quantity, Carbon $startDate, Carbon $endDate): array
    {
        $assignedItems = [];

        foreach ($bundling->products as $product) {
            $requiredQty = $quantity * ($product->pivot->quantity ?? 1);
            $availableSerials = $product->getAvailableSerialNumbersForPeriod($startDate->format('Y-m-d'), $endDate->format('Y-m-d'));
            
            $requiredSerials = array_slice($availableSerials, 0, $requiredQty);
            if (count($requiredSerials) < $requiredQty) {
                throw new \Exception("Insufficient {$product->name} items for bundling");
            }

            $productItemIds = ProductItem::whereIn('serial_number', $requiredSerials)->pluck('id');
            $assignedItems = array_merge($assignedItems, $productItemIds->toArray());
        }

        return $assignedItems;
    }

    /**
     * Update transaction status with business logic
     */
    public function updateTransactionStatus(Transaction $transaction, string $status): bool
    {
        $oldStatus = $transaction->booking_status;

        // Validate status transition
        if (!$this->isValidStatusTransition($oldStatus, $status)) {
            throw new \Exception("Invalid status transition from {$oldStatus} to {$status}");
        }

        // Apply business logic based on status
        $updates = ['booking_status' => $status];

        switch ($status) {
            case 'paid':
                $updates['down_payment'] = $transaction->grand_total;
                $updates['remaining_payment'] = 0;
                break;

            case 'cancel':
                $cancellationFee = (int)floor($transaction->grand_total * 0.5);
                $updates['cancellation_fee'] = $cancellationFee;
                $updates['down_payment'] = $cancellationFee;
                $updates['remaining_payment'] = 0;
                break;

            case 'on_rented':
                $updates['down_payment'] = $transaction->grand_total;
                $updates['remaining_payment'] = 0;
                break;
        }

        $updated = $transaction->update($updates);

        if ($updated) {
            // Clear related caches
            $this->clearTransactionCaches($transaction);
            
            // Log status change
            Log::info('Transaction status updated', [
                'transaction_id' => $transaction->id,
                'old_status' => $oldStatus,
                'new_status' => $status,
                'updated_by' => auth()->id(),
            ]);
        }

        return $updated;
    }

    /**
     * Validate if status transition is allowed
     */
    protected function isValidStatusTransition(string $from, string $to): bool
    {
        $allowedTransitions = [
            'booking' => ['paid', 'cancel', 'on_rented'],
            'paid' => ['on_rented', 'cancel'],
            'on_rented' => ['done', 'cancel'],
            'done' => [], // Final state
            'cancel' => [], // Final state
        ];

        return in_array($to, $allowedTransitions[$from] ?? []);
    }

    /**
     * Clear transaction-related caches
     */
    protected function clearTransactionCaches(Transaction $transaction): void
    {
        // Clear product availability caches
        foreach ($transaction->detailTransactions as $detail) {
            if ($detail->product_id) {
                Cache::forget("product_availability_{$detail->product_id}_*");
                Cache::forget("product_serials_{$detail->product_id}_*");
            }
        }

        // Clear transaction search cache
        Cache::forget("transaction_search_*");
    }

    /**
     * Get transaction statistics
     */
    public function getTransactionStatistics(): array
    {
        return Cache::remember('transaction_statistics', now()->addHours(1), function () {
            return [
                'total_transactions' => Transaction::count(),
                'active_rentals' => Transaction::whereIn('booking_status', ['booking', 'paid', 'on_rented'])->count(),
                'completed_rentals' => Transaction::where('booking_status', 'done')->count(),
                'cancelled_transactions' => Transaction::where('booking_status', 'cancel')->count(),
                'total_revenue' => Transaction::where('booking_status', '!=', 'cancel')->sum('grand_total'),
                'pending_payments' => Transaction::whereIn('booking_status', ['booking', 'paid', 'on_rented'])->sum('remaining_payment'),
            ];
        });
    }
}
