<?php
/**
 * Quick script to check queue setup for customer import
 * Run this to verify queue system is working
 */

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Checking Queue Setup for Customer Import...\n\n";

// Check if jobs table exists
try {
    $jobsCount = DB::table('jobs')->count();
    echo "✅ Jobs table exists - Current jobs: {$jobsCount}\n";
} catch (Exception $e) {
    echo "❌ Jobs table error: " . $e->getMessage() . "\n";
    echo "Run: php artisan migrate\n\n";
    exit(1);
}

// Check if failed_jobs table exists
try {
    $failedJobsCount = DB::table('failed_jobs')->count();
    echo "✅ Failed jobs table exists - Failed jobs: {$failedJobsCount}\n";
} catch (Exception $e) {
    echo "❌ Failed jobs table error: " . $e->getMessage() . "\n";
    echo "Run: php artisan queue:failed-table && php artisan migrate\n\n";
    exit(1);
}

// Check if cache is working
try {
    $testKey = 'queue_test_' . time();
    cache()->put($testKey, 'test_value', 60);
    $retrieved = cache()->get($testKey);
    
    if ($retrieved === 'test_value') {
        echo "✅ Cache system is working\n";
        cache()->forget($testKey);
    } else {
        echo "❌ Cache system not working properly\n";
    }
} catch (Exception $e) {
    echo "❌ Cache error: " . $e->getMessage() . "\n";
}

// Test queue configuration
echo "\n📋 Queue Configuration:\n";
echo "Queue Driver: " . config('queue.default') . "\n";
echo "Queue Connection: " . config('queue.connections.' . config('queue.default') . '.driver') . "\n";

// Check if import service exists
try {
    $service = new App\Services\CustomerImportExportService();
    echo "✅ CustomerImportExportService exists\n";
} catch (Exception $e) {
    echo "❌ CustomerImportExportService error: " . $e->getMessage() . "\n";
}

// Check if import job exists
try {
    $reflection = new ReflectionClass('App\\Jobs\\ImportCustomersJob');
    echo "✅ ImportCustomersJob class exists\n";
} catch (Exception $e) {
    echo "❌ ImportCustomersJob error: " . $e->getMessage() . "\n";
}

echo "\n🚀 Commands to run on production server:\n";
echo "1. Start queue worker: php artisan queue:work --queue=imports --timeout=300 --memory=512\n";
echo "2. Monitor queue: php artisan queue:monitor\n";
echo "3. Check failed jobs: php artisan queue:failed\n";
echo "4. Test with small file first (< 1MB) to verify sync import works\n";
echo "5. Then test with large file (> 1MB) to verify async import works\n";

echo "\n📊 Import Strategy:\n";
echo "- Files ≤ 1MB: Synchronous import (immediate response)\n";
echo "- Files > 1MB: Asynchronous import (background job)\n";

echo "\nDone! ✨\n";
