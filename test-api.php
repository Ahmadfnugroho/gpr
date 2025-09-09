<?php

/**
 * Simple test script to verify ProductController API functionality
 * Run with: php test-api.php
 */

function testAPI($url, $description) {
    echo "\n=== Testing: $description ===\n";
    echo "URL: $url\n";
    
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
    
    $startTime = microtime(true);
    $response = file_get_contents($url, false, $context);
    $endTime = microtime(true);
    
    if ($response === false) {
        echo "❌ ERROR: Failed to fetch data\n";
        return;
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ ERROR: Invalid JSON response\n";
        return;
    }
    
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    echo "✅ SUCCESS: Response received in {$responseTime}ms\n";
    
    // Check if it's collection or single resource
    if (isset($data['data']) && is_array($data['data'])) {
        if (isset($data['data'][0])) {
            // Collection
            echo "📦 Collection response with " . count($data['data']) . " items\n";
            
            // Check first item for available_quantity
            $firstItem = $data['data'][0];
            if (isset($firstItem['available_quantity'])) {
                echo "✅ available_quantity found: " . $firstItem['available_quantity'] . "\n";
                echo "📊 quantity: " . ($firstItem['quantity'] ?? 'N/A') . "\n";
                echo "🔍 Product: " . ($firstItem['name'] ?? 'N/A') . "\n";
            } else {
                echo "❌ available_quantity field missing\n";
            }
        } else {
            // Single resource
            $item = $data['data'];
            echo "📱 Single resource response\n";
            
            if (isset($item['available_quantity'])) {
                echo "✅ available_quantity found: " . $item['available_quantity'] . "\n";
                echo "📊 quantity: " . ($item['quantity'] ?? 'N/A') . "\n";
                echo "🔍 Product: " . ($item['name'] ?? 'N/A') . "\n";
            } else {
                echo "❌ available_quantity field missing\n";
            }
        }
    } else {
        echo "⚠️  Unexpected response structure\n";
    }
    
    echo "---\n";
}

// Base URL - adjust if needed
$baseUrl = 'http://gpr.id/api';

// Test cases
$testCases = [
    // Products collection tests
    [
        'url' => "$baseUrl/products?limit=3",
        'description' => 'Products collection without dates'
    ],
    [
        'url' => "$baseUrl/products?start_date=2025-12-25&end_date=2025-12-27&limit=3",
        'description' => 'Products collection with date range'
    ],
    
    // Single product tests
    [
        'url' => "$baseUrl/product/sony-a7c",
        'description' => 'Single product without dates'
    ],
    [
        'url' => "$baseUrl/product/sony-a7c?start_date=2025-12-25&end_date=2025-12-27",
        'description' => 'Single product with date range'
    ],
    
    // Validation tests
    [
        'url' => "$baseUrl/product/sony-a7c?start_date=invalid",
        'description' => 'Invalid date format (should return validation error)'
    ],
    [
        'url' => "$baseUrl/product/sony-a7c?start_date=2025-12-27&end_date=2025-12-25",
        'description' => 'End date before start date (should return validation error)'
    ]
];

echo "🚀 Starting API Tests...\n";
echo "========================\n";

foreach ($testCases as $test) {
    testAPI($test['url'], $test['description']);
    
    // Small delay between requests
    usleep(500000); // 0.5 seconds
}

echo "\n✅ All tests completed!\n";
echo "\n📝 Next steps:\n";
echo "1. Check that available_quantity appears in all responses\n";
echo "2. Verify date range filtering affects the available_quantity values\n";
echo "3. Test with real booking data to see quantity differences\n";
echo "4. Test frontend integration with the rental duration helper\n";

?>
