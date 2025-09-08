<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\Transaction;
use App\Models\DetailTransaction;
use App\Models\ProductItem;
use App\Models\DetailTransactionProductItem;

echo "=== Testing Product Availability Relationships ===\n\n";

try {
    // Test 1: Get first product
    $product = Product::first();
    if (!$product) {
        echo "No products found in database\n";
        exit(1);
    }
    
    echo "1. Testing with product: {$product->name} (ID: {$product->id})\n";
    
    // Test 2: Check product items
    $productItems = ProductItem::where('product_id', $product->id)->get();
    echo "   - Product items count: {$productItems->count()}\n";
    
    if ($productItems->count() > 0) {
        echo "   - Sample serial numbers: " . $productItems->take(3)->pluck('serial_number')->implode(', ') . "\n";
    }
    
    // Test 3: Check active transactions for this product
    echo "\n2. Checking active transactions for this product:\n";
    
    // Method 1: Direct query
    $directTransactions = Transaction::whereIn('booking_status', ['booking', 'paid', 'on_rented'])
        ->whereHas('detailTransactions', function($q) use ($product) {
            $q->where('product_id', $product->id);
        })
        ->get();
        
    echo "   - Direct product_id query: {$directTransactions->count()} transactions\n";
    
    // Method 2: Through product items (pivot table)
    $pivotTransactions = Transaction::whereIn('booking_status', ['booking', 'paid', 'on_rented'])
        ->whereHas('detailTransactions.productItems', function($q) use ($product) {
            $q->where('product_id', $product->id);
        })
        ->get();
        
    echo "   - Through pivot table: {$pivotTransactions->count()} transactions\n";
    
    // Test 4: Check detail transactions
    echo "\n3. Detail transactions for this product:\n";
    
    $detailTransactions = DetailTransaction::where('product_id', $product->id)
        ->with(['transaction', 'productItems'])
        ->get();
        
    echo "   - Direct detail transactions: {$detailTransactions->count()}\n";
    
    foreach ($detailTransactions as $detail) {
        $trans = $detail->transaction;
        $productItemsCount = $detail->productItems ? $detail->productItems->count() : 0;
        echo "     * Transaction: {$trans->booking_transaction_id} | Status: {$trans->booking_status} | Items: {$productItemsCount}\n";
    }
    
    // Test 5: Check pivot table entries
    echo "\n4. Checking pivot table entries:\n";
    
    $pivotEntries = DetailTransactionProductItem::whereHas('productItem', function($q) use ($product) {
        $q->where('product_id', $product->id);
    })
    ->with(['detailTransaction.transaction', 'productItem'])
    ->get();
    
    echo "   - Pivot table entries: {$pivotEntries->count()}\n";
    
    foreach ($pivotEntries as $pivot) {
        $trans = $pivot->detailTransaction->transaction;
        echo "     * Transaction: {$trans->booking_transaction_id} | Status: {$trans->booking_status} | Serial: {$pivot->productItem->serial_number}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
