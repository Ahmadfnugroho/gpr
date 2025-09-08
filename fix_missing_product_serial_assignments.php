<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Transaction;
use App\Models\Product;
use App\Models\ProductItem;
use App\Models\DetailTransaction;
use App\Models\DetailTransactionProductItem;

echo "🔧 Fix Missing Product Serial Assignments\n";
echo "This script identifies and fixes transactions where products have no serial numbers assigned\n\n";

// Find detail transactions that have products but no pivot table entries
$problematicDetails = DetailTransaction::whereNotNull('product_id')
    ->whereDoesntHave('productItems')
    ->whereHas('transaction', function ($query) {
        $query->whereIn('booking_status', ['booking', 'paid', 'on_rented']);
    })
    ->with(['product', 'transaction'])
    ->get();

echo "Found " . $problematicDetails->count() . " detail transactions with products but no serial assignments\n\n";

foreach ($problematicDetails as $detail) {
    echo "Transaction: {$detail->transaction->invoice_number}\n";
    echo "Product: {$detail->product->name} (ID: {$detail->product_id})\n";
    echo "Detail Transaction ID: {$detail->id}\n";
    
    // Check if this product has available items
    $availableItems = ProductItem::where('product_id', $detail->product_id)
        ->where('is_available', true)
        ->get();
    
    echo "Available product items: " . $availableItems->count() . "\n";
    
    if ($availableItems->count() > 0) {
        // Show the first available item as an example
        $firstItem = $availableItems->first();
        echo "Example available item: {$firstItem->serial_number} (ID: {$firstItem->id})\n";
        
        echo "💡 To fix this transaction, you can run:\n";
        echo "DetailTransactionProductItem::create([\n";
        echo "    'detail_transaction_id' => {$detail->id},\n";
        echo "    'product_item_id' => {$firstItem->id}, // {$firstItem->serial_number}\n";
        echo "]);\n\n";
    } else {
        echo "⚠️  No available product items found for this product\n";
        echo "💡 You need to create product items (serial numbers) for this product first\n\n";
    }
    
    echo "---\n";
}

if ($problematicDetails->count() > 0) {
    echo "\n🎯 ROOT CAUSE ANALYSIS:\n";
    echo "The issue occurs when:\n";
    echo "1. A transaction includes a product in detail_transactions table\n";
    echo "2. BUT no entries are created in detail_transaction_product_item pivot table\n";
    echo "3. This happens when serial numbers aren't assigned during transaction creation\n\n";
    
    echo "🔧 SOLUTION OPTIONS:\n";
    echo "1. MANUAL FIX: Run the DetailTransactionProductItem::create() commands above\n";
    echo "2. SYSTEMATIC FIX: Improve the transaction creation process to auto-assign serial numbers\n";
    echo "3. UI FIX: Add validation to ensure serial numbers are assigned before saving transactions\n\n";
    
    echo "🚀 AFTER FIXING:\n";
    echo "- ProductAvailability will show correct rental information\n";
    echo "- 'Current Rentals' column will display active transactions\n";
    echo "- 'No serial numbers' will change to show actual serial numbers\n";
} else {
    echo "✅ No problematic transactions found! All products have proper serial assignments.\n";
}

echo "\n📝 NOTE: This script only identifies the issue. Run the suggested commands manually\n";
echo "or implement the systematic fixes to resolve the problem permanently.\n";
