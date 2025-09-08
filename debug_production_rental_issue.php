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

echo "🔍 Debug script for production rental issue - GPR35651\n";
echo "Run this on your production server to identify the problem\n\n";

echo "=== STEP 1: Find the transaction ===\n";
echo '$transaction = Transaction::where("invoice_number", "GPR35651")->first();' . "\n";
echo 'if (!$transaction) { echo "Transaction not found"; exit; }' . "\n";
echo 'echo "Found transaction: " . $transaction->id . " Status: " . $transaction->booking_status;' . "\n\n";

echo "=== STEP 2: Check detail transactions ===\n";
echo '$detailTransactions = DetailTransaction::where("transaction_id", $transaction->id)->get();' . "\n";
echo 'echo "Detail transactions count: " . $detailTransactions->count();' . "\n\n";

echo "=== STEP 3: Find Sony FE 28mm F2 detail transaction ===\n";
echo '$sonyProduct = Product::where("name", "LIKE", "%Sony FE 28mm F2%")->first();' . "\n";
echo 'if (!$sonyProduct) { echo "Sony product not found"; exit; }' . "\n";
echo 'echo "Sony product ID: " . $sonyProduct->id;' . "\n\n";

echo '$sonyDetail = $detailTransactions->where("product_id", $sonyProduct->id)->first();' . "\n";
echo 'if (!$sonyDetail) { echo "Sony detail transaction not found"; exit; }' . "\n";
echo 'echo "Sony detail transaction ID: " . $sonyDetail->id;' . "\n\n";

echo "=== STEP 4: Check pivot table entries (THIS IS THE PROBLEM) ===\n";
echo '$pivotEntries = DetailTransactionProductItem::where("detail_transaction_id", $sonyDetail->id)->get();' . "\n";
echo 'echo "Pivot entries for Sony detail: " . $pivotEntries->count();' . "\n";
echo 'if ($pivotEntries->count() == 0) {' . "\n";
echo '    echo "🚨 PROBLEM FOUND: No pivot table entries for Sony FE 28mm F2!";' . "\n";
echo '}' . "\n\n";

echo "=== STEP 5: Check if Sony has product items ===\n";
echo '$sonyItems = ProductItem::where("product_id", $sonyProduct->id)->get();' . "\n";
echo 'echo "Sony product items count: " . $sonyItems->count();' . "\n";
echo 'foreach ($sonyItems as $item) {' . "\n";
echo '    echo "  - Serial: " . $item->serial_number . " (ID: " . $item->id . ")";' . "\n";
echo '}' . "\n\n";

echo "=== STEP 6: Test ProductAvailability query ===\n";
echo '$testQuery = Transaction::whereIn("booking_status", ["booking", "paid", "on_rented"])' . "\n";
echo '    ->whereHas("detailTransactions.productItems", function ($query) use ($sonyProduct) {' . "\n";
echo '        $query->where("product_id", $sonyProduct->id);' . "\n";
echo '    })->get();' . "\n";
echo 'echo "ProductAvailability query result: " . $testQuery->count() . " transactions";' . "\n\n";

echo "=== THE SOLUTION ===\n";
echo "If pivot entries count is 0, you need to:\n";
echo "1. Find a Sony FE 28mm F2 product item (serial number)\n";
echo "2. Create a pivot table entry manually or through the transaction form\n";
echo "3. Link the product item to the detail transaction\n\n";

echo "Example fix (run after identifying available product item ID):\n";
echo 'DetailTransactionProductItem::create([' . "\n";
echo '    "detail_transaction_id" => $sonyDetail->id,' . "\n";
echo '    "product_item_id" => $availableProductItemId, // Replace with actual ID' . "\n";
echo ']);' . "\n\n";

echo "After running this fix, the ProductAvailability should show the rental correctly.\n";
