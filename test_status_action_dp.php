<?php

echo "=== TESTING STATUS ACTION DOWN PAYMENT OVERRIDE ===" . PHP_EOL;

$transaction = App\Models\Transaction::with(['detailTransactions.product', 'detailTransactions.bundling', 'promo'])->first();

if ($transaction) {
    echo "Transaction ID: {$transaction->id}" . PHP_EOL;
    echo "Current Status: {$transaction->booking_status}" . PHP_EOL;
    echo "Current Grand Total: Rp " . number_format($transaction->grand_total, 0, ',', '.') . PHP_EOL;
    
    // Set custom down payment first
    $customDownPayment = 100000;
    
    // Reset to booking status and set custom down payment
    App\Models\Transaction::where('id', $transaction->id)->update([
        'booking_status' => 'booking',
        'down_payment' => $customDownPayment,
        'remaining_payment' => $transaction->grand_total - $customDownPayment
    ]);
    
    $transaction = $transaction->fresh();
    echo "Reset to 'booking' status with Custom Down Payment: Rp " . number_format($transaction->down_payment, 0, ',', '.') . PHP_EOL;
    echo "Remaining Payment: Rp " . number_format($transaction->remaining_payment, 0, ',', '.') . PHP_EOL;
    
    echo PHP_EOL . "=== SIMULATING STATUS ACTION 'paid' (LIKE FIXED STATUSACTIONS.PHP) ===" . PHP_EOL;
    
    // Simulate what our fixed StatusActions.php does
    $transaction->load(['detailTransactions.product', 'detailTransactions.bundling', 'promo']);
    
    // Use calculated grand total (includes additional services)
    $correctGrandTotal = $transaction->calculateGrandTotal();
    echo "Correct Grand Total: Rp " . number_format($correctGrandTotal, 0, ',', '.') . PHP_EOL;
    
    // THIS IS THE PROBLEM! StatusAction overwrites down_payment with grand_total
    $transaction->update([
        'booking_status' => 'paid',
        'grand_total' => $correctGrandTotal,
        'down_payment' => $correctGrandTotal, // ← THIS OVERWRITES CUSTOM DOWN_PAYMENT!
    ]);
    
    $updated = $transaction->fresh();
    echo PHP_EOL . "After StatusAction 'paid':" . PHP_EOL;
    echo "  Status: {$updated->booking_status}" . PHP_EOL;
    echo "  Grand Total: Rp " . number_format($updated->grand_total, 0, ',', '.') . PHP_EOL;
    echo "  Down Payment: Rp " . number_format($updated->down_payment, 0, ',', '.') . PHP_EOL;
    echo "  Remaining Payment: Rp " . number_format($updated->remaining_payment, 0, ',', '.') . PHP_EOL;
    
    // Check if custom down payment was preserved
    $preserved = ($updated->down_payment == $customDownPayment);
    echo "  Custom Down Payment Preserved: " . ($preserved ? 'YES ✅' : 'NO ❌') . PHP_EOL;
    
    if (!$preserved) {
        echo "  Expected: Rp " . number_format($customDownPayment, 0, ',', '.') . PHP_EOL;
        echo "  Actual: Rp " . number_format($updated->down_payment, 0, ',', '.') . PHP_EOL;
        echo "  ❌ PROBLEM FOUND: StatusAction is setting down_payment = grand_total!" . PHP_EOL;
        echo "     This overwrites any custom down_payment the user set!" . PHP_EOL;
    }
    
} else {
    echo "No transactions found for testing." . PHP_EOL;
}

echo PHP_EOL . "=== STATUS ACTION DOWN PAYMENT TEST COMPLETED ===" . PHP_EOL;
