<?php

/**
 * Check available bundlings data
 */

// Base URL
$baseUrl = 'http://gpr.id/api';

function fetchAPI($url) {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Accept: application/json',
                'Content-Type: application/json'
            ],
            'timeout' => 30
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "❌ ERROR: Failed to fetch data from $url\n";
        return false;
    }
    
    return json_decode($response, true);
}

echo "🔍 Checking available bundlings...\n";
echo "==================================\n\n";

// 1. Check bundlings collection
echo "1. Fetching bundlings collection:\n";
$bundlingsData = fetchAPI("$baseUrl/bundlings?limit=10");

if ($bundlingsData && isset($bundlingsData['data'])) {
    if (empty($bundlingsData['data'])) {
        echo "⚠️  No bundlings found in collection\n";
    } else {
        echo "✅ Found " . count($bundlingsData['data']) . " bundlings:\n";
        foreach ($bundlingsData['data'] as $bundling) {
            echo "   - ID: {$bundling['id']}, Name: {$bundling['name']}, Slug: {$bundling['slug']}\n";
        }
        
        // Try to access the first bundling by slug
        if (!empty($bundlingsData['data'])) {
            $firstBundling = $bundlingsData['data'][0];
            $firstSlug = $firstBundling['slug'];
            
            echo "\n2. Testing single bundling access:\n";
            echo "   Trying URL: $baseUrl/bundling/$firstSlug\n";
            
            $singleBundling = fetchAPI("$baseUrl/bundling/$firstSlug");
            if ($singleBundling && isset($singleBundling['data'])) {
                echo "   ✅ Successfully fetched single bundling: {$singleBundling['data']['name']}\n";
                
                // Test with date parameters
                echo "\n3. Testing with date parameters:\n";
                echo "   Trying URL: $baseUrl/bundling/$firstSlug?start_date=2025-12-25&end_date=2025-12-27\n";
                
                $bundlingWithDates = fetchAPI("$baseUrl/bundling/$firstSlug?start_date=2025-12-25&end_date=2025-12-27");
                if ($bundlingWithDates && isset($bundlingWithDates['data'])) {
                    echo "   ✅ Successfully fetched with dates\n";
                    
                    // Check if products have available_quantity
                    if (isset($bundlingWithDates['data']['products'])) {
                        echo "   📦 Products in bundling:\n";
                        foreach ($bundlingWithDates['data']['products'] as $product) {
                            $availableQty = $product['available_quantity'] ?? 'N/A';
                            $totalQty = $product['quantity'] ?? 'N/A';
                            echo "      - {$product['name']}: {$availableQty} available (bundling qty: {$totalQty})\n";
                        }
                    }
                } else {
                    echo "   ❌ Failed to fetch with date parameters\n";
                }
            } else {
                echo "   ❌ Failed to fetch single bundling\n";
            }
        }
    }
} else {
    echo "❌ Failed to fetch bundlings collection or invalid response\n";
    if ($bundlingsData) {
        echo "Response: " . json_encode($bundlingsData, JSON_PRETTY_PRINT) . "\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "💡 Tips:\n";
echo "- Make sure bundlings exist in database\n";
echo "- Check the exact slug format\n";
echo "- Verify routes are properly configured\n";
echo "- Test the working URL from the output above\n";

?>
