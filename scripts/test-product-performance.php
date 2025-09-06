<?php

/**
 * Performance Test Script for ProductResource
 * Run: php scripts/test-product-performance.php
 */

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

echo "\n🚀 ProductResource Performance Test\n";
echo "===================================\n\n";

// Clear cache first
Cache::flush();
echo "Cache cleared.\n\n";

// Test 1: Regular query vs Optimized query
echo "📊 Test 1: Query Performance Comparison\n";
echo "-" . str_repeat("-", 40) . "\n";

// Enable query logging
DB::enableQueryLog();

$startTime = microtime(true);

// Original query (without optimization)
$originalQuery = Product::withCount('items')->get();

$originalTime = microtime(true) - $startTime;
$originalQueries = DB::getQueryLog();
$originalQueryCount = count($originalQueries);

// Clear query log
DB::flushQueryLog();

$startTime = microtime(true);

// Optimized query
$optimizedQuery = Product::select([
    'products.id',
    'products.name', 
    'products.status',
    'products.premiere',
    'products.category_id',
    'products.brand_id',
    'products.sub_category_id'
])
->withCount('items')
->with([
    'category:id,name',
    'brand:id,name',
    'subCategory:id,name'
])
->limit(500)
->get();

$optimizedTime = microtime(true) - $startTime;
$optimizedQueries = DB::getQueryLog();
$optimizedQueryCount = count($optimizedQueries);

echo "Original Query:\n";
echo "  ⏱️  Time: " . number_format($originalTime, 4) . " seconds\n";
echo "  📊 Queries: $originalQueryCount\n";
echo "  📦 Records: " . $originalQuery->count() . "\n\n";

echo "Optimized Query:\n";
echo "  ⏱️  Time: " . number_format($optimizedTime, 4) . " seconds\n";
echo "  📊 Queries: $optimizedQueryCount\n";
echo "  📦 Records: " . $optimizedQuery->count() . "\n\n";

$improvement = (($originalTime - $optimizedTime) / $originalTime) * 100;
echo "🎯 Performance Improvement: " . number_format($improvement, 2) . "%\n\n";

// Test 2: Global Search Performance
echo "📊 Test 2: Global Search Performance\n";
echo "-" . str_repeat("-", 40) . "\n";

DB::flushQueryLog();

$startTime = microtime(true);

// Test global search
$searchResults = Product::select(['id', 'name', 'status', 'price'])
    ->with(['category:id,name', 'brand:id,name'])
    ->where('status', '!=', 'deleted')
    ->whereRaw('LOWER(name) LIKE ?', ['%canon%'])
    ->limit(50)
    ->get();

$searchTime = microtime(true) - $startTime;
$searchQueries = DB::getQueryLog();
$searchQueryCount = count($searchQueries);

echo "Global Search Results:\n";
echo "  ⏱️  Time: " . number_format($searchTime, 4) . " seconds\n";
echo "  📊 Queries: $searchQueryCount\n";
echo "  📦 Results: " . $searchResults->count() . "\n\n";

// Test 3: Availability Calculation Performance
echo "📊 Test 3: Availability Calculation Performance\n";
echo "-" . str_repeat("-", 50) . "\n";

$testProduct = Product::with('items')->first();

if ($testProduct) {
    DB::flushQueryLog();
    
    $startTime = microtime(true);
    
    // Test availability calculation (this will use cache after first call)
    $availability1 = $testProduct->is_available;
    $firstCallTime = microtime(true) - $startTime;
    $firstCallQueries = DB::getQueryLog();
    $firstCallQueryCount = count($firstCallQueries);
    
    DB::flushQueryLog();
    $startTime = microtime(true);
    
    // Second call should use cache
    $availability2 = $testProduct->is_available;
    $secondCallTime = microtime(true) - $startTime;
    $secondCallQueries = DB::getQueryLog();
    $secondCallQueryCount = count($secondCallQueries);
    
    echo "Availability Calculation for Product: " . $testProduct->name . "\n";
    echo "First Call (no cache):\n";
    echo "  ⏱️  Time: " . number_format($firstCallTime, 4) . " seconds\n";
    echo "  📊 Queries: $firstCallQueryCount\n";
    echo "  ✅ Available: " . ($availability1 ? 'Yes' : 'No') . "\n\n";
    
    echo "Second Call (with cache):\n";
    echo "  ⏱️  Time: " . number_format($secondCallTime, 4) . " seconds\n";
    echo "  📊 Queries: $secondCallQueryCount\n";
    echo "  ✅ Available: " . ($availability2 ? 'Yes' : 'No') . "\n\n";
    
    $cacheImprovement = (($firstCallTime - $secondCallTime) / $firstCallTime) * 100;
    echo "🎯 Cache Performance Improvement: " . number_format($cacheImprovement, 2) . "%\n\n";
}

// Test 4: Memory Usage
echo "📊 Test 4: Memory Usage\n";
echo "-" . str_repeat("-", 24) . "\n";

$memoryBefore = memory_get_usage();
$peakMemoryBefore = memory_get_peak_usage();

// Load products with relationships
$productsWithRelations = Product::with([
    'category:id,name',
    'brand:id,name', 
    'subCategory:id,name'
])->limit(100)->get();

$memoryAfter = memory_get_usage();
$peakMemoryAfter = memory_get_peak_usage();

$memoryUsed = $memoryAfter - $memoryBefore;
$peakMemoryUsed = $peakMemoryAfter - $peakMemoryBefore;

echo "Memory Usage for 100 Products:\n";
echo "  📊 Used: " . number_format($memoryUsed / 1024 / 1024, 2) . " MB\n";
echo "  📊 Peak: " . number_format($peakMemoryUsed / 1024 / 1024, 2) . " MB\n";
echo "  📦 Records: " . $productsWithRelations->count() . "\n\n";

// Test 5: Database Statistics
echo "📊 Test 5: Database Statistics\n";
echo "-" . str_repeat("-", 31) . "\n";

$productCount = Product::count();
$productItemCount = DB::table('product_items')->count();
$transactionCount = DB::table('transactions')->count();
$detailTransactionCount = DB::table('detail_transactions')->count();

echo "Database Record Counts:\n";
echo "  📦 Products: " . number_format($productCount) . "\n";
echo "  📦 Product Items: " . number_format($productItemCount) . "\n";
echo "  📦 Transactions: " . number_format($transactionCount) . "\n";
echo "  📦 Detail Transactions: " . number_format($detailTransactionCount) . "\n\n";

// Recommendations
echo "💡 Performance Recommendations\n";
echo "=" . str_repeat("=", 31) . "\n";
echo "1. ✅ Run database migration with indexes:\n";
echo "   php artisan migrate\n\n";
echo "2. ✅ Clear product caches regularly:\n";
echo "   php artisan product:clear-cache\n\n";
echo "3. ✅ Monitor query performance:\n";
echo "   - Use Laravel Debugbar in development\n";
echo "   - Monitor slow query logs in production\n\n";
echo "4. ✅ Consider Redis for caching:\n";
echo "   - Configure Redis cache driver\n";
echo "   - Use for session and queue drivers\n\n";

if ($optimizedQueryCount < $originalQueryCount) {
    echo "5. ✅ Query optimization is working!\n";
    echo "   - Reduced queries from $originalQueryCount to $optimizedQueryCount\n";
    echo "   - Performance improved by " . number_format($improvement, 1) . "%\n\n";
}

echo "🎉 Performance test completed!\n";
echo "Check the results above to monitor your ProductResource performance.\n\n";
