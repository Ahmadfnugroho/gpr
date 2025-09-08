<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Checking Database Table Structures ===\n\n";

// Check detail_transactions table
echo "1. detail_transactions table:\n";
try {
    $columns = DB::select('DESCRIBE detail_transactions');
    foreach($columns as $column) {
        $nullable = $column->Null === 'YES' ? 'NULL' : 'NOT NULL';
        echo "   - {$column->Field} ({$column->Type}) - {$nullable}\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n2. detail_transaction_product_items table:\n";
try {
    $columns = DB::select('DESCRIBE detail_transaction_product_items');
    foreach($columns as $column) {
        $nullable = $column->Null === 'YES' ? 'NULL' : 'NOT NULL';
        echo "   - {$column->Field} ({$column->Type}) - {$nullable}\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n3. product_items table:\n";
try {
    $columns = DB::select('DESCRIBE product_items');
    foreach($columns as $column) {
        $nullable = $column->Null === 'YES' ? 'NULL' : 'NOT NULL';
        echo "   - {$column->Field} ({$column->Type}) - {$nullable}\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n4. transactions table:\n";
try {
    $columns = DB::select('DESCRIBE transactions');
    foreach($columns as $column) {
        $nullable = $column->Null === 'YES' ? 'NULL' : 'NOT NULL';
        echo "   - {$column->Field} ({$column->Type}) - {$nullable}\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== Checking Sample Data ===\n\n";

// Check if there are active transactions for products
echo "5. Sample Active Product Transactions:\n";
try {
    $activeTransactions = DB::select("
        SELECT 
            t.id as transaction_id,
            t.booking_transaction_id,
            t.booking_status,
            t.start_date,
            t.end_date,
            dt.id as detail_id,
            dt.product_id,
            dt.bundling_id,
            p.name as product_name,
            COUNT(dtpi.id) as product_items_count
        FROM transactions t
        JOIN detail_transactions dt ON t.id = dt.transaction_id
        LEFT JOIN products p ON dt.product_id = p.id
        LEFT JOIN detail_transaction_product_items dtpi ON dt.id = dtpi.detail_transaction_id
        WHERE t.booking_status IN ('booking', 'paid', 'on_rented')
          AND dt.product_id IS NOT NULL
        GROUP BY t.id, dt.id
        LIMIT 5
    ");
    
    if (empty($activeTransactions)) {
        echo "   No active product transactions found\n";
    } else {
        foreach($activeTransactions as $trans) {
            echo "   - Transaction: {$trans->booking_transaction_id} | Product: {$trans->product_name} | Status: {$trans->booking_status} | Items: {$trans->product_items_count}\n";
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n6. Sample Active Bundling Transactions:\n";
try {
    $activeBundlings = DB::select("
        SELECT 
            t.id as transaction_id,
            t.booking_transaction_id,
            t.booking_status,
            t.start_date,
            t.end_date,
            dt.id as detail_id,
            dt.bundling_id,
            b.name as bundling_name
        FROM transactions t
        JOIN detail_transactions dt ON t.id = dt.transaction_id
        JOIN bundlings b ON dt.bundling_id = b.id
        WHERE t.booking_status IN ('booking', 'paid', 'on_rented')
          AND dt.bundling_id IS NOT NULL
        LIMIT 5
    ");
    
    if (empty($activeBundlings)) {
        echo "   No active bundling transactions found\n";
    } else {
        foreach($activeBundlings as $trans) {
            echo "   - Transaction: {$trans->booking_transaction_id} | Bundling: {$trans->bundling_name} | Status: {$trans->booking_status}\n";
        }
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n=== Check Complete ===\n";
