<?php

use App\Models\Product;
use App\Models\ProductAvailability;
use App\Models\Transaction;
use Carbon\Carbon;

Route::get('/test-availability/{product_id}', function ($product_id) {
    $result = [
        'timestamp' => now()->format('Y-m-d H:i:s'),
        'product_id' => $product_id,
        'tests' => []
    ];

    try {
        $product = Product::findOrFail($product_id);
        $availability = new ProductAvailability();
        
        // Test Case 1: Check current availability
        $now = Carbon::now();
        $threeDays = $now->copy()->addDays(3);
        
        $test1 = [
            'case' => 'Current Availability',
            'period' => "{$now->format('Y-m-d')} to {$threeDays->format('Y-m-d')}",
            'total_items' => $product->items()->count(),
            'available_items' => $availability->getAvailableProductItems($product_id, $now, $threeDays)
        ];
        $result['tests'][] = $test1;

        // Test Case 2: Check active rentals
        $activeRentals = Transaction::whereIn('booking_status', ['booking', 'paid', 'on_rented'])
            ->whereHas('detailTransactions.productItems', function($query) use ($product_id) {
                $query->where('product_id', $product_id);
            })
            ->with(['detailTransactions.productItems'])
            ->get();

        $test2 = [
            'case' => 'Active Rentals',
            'count' => $activeRentals->count(),
            'rentals' => $activeRentals->map(function($rental) {
                return [
                    'id' => $rental->id,
                    'status' => $rental->booking_status,
                    'period' => "{$rental->start_date} to {$rental->end_date}"
                ];
            })
        ];
        $result['tests'][] = $test2;

        // Test Case 3: Check specific future date
        $nextWeek = $now->copy()->addWeek();
        $nextWeekPlus3 = $nextWeek->copy()->addDays(3);
        
        $test3 = [
            'case' => 'Future Availability',
            'period' => "{$nextWeek->format('Y-m-d')} to {$nextWeekPlus3->format('Y-m-d')}",
            'available_items' => $availability->getAvailableProductItems($product_id, $nextWeek, $nextWeekPlus3)
        ];
        $result['tests'][] = $test3;

        return response()->json($result);

    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
})->name('test.availability');
