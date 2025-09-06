<?php

/**
 * Comprehensive Endpoint Testing Script
 * Tests all API endpoints and key admin routes using admin.globalphotorental.com
 */

// Base URL
$baseUrl = 'https://admin.globalphotorental.com';

// Function to make HTTP request and test response
function testEndpoint($url, $method = 'GET', $data = null, $headers = [])
{
    $ch = curl_init();
    
    // Common curl options
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false, // For development/testing
        CURLOPT_USERAGENT => 'GPR-Endpoint-Tester/1.0'
    ]);
    
    // Add headers
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    
    // Set method and data
    switch (strtoupper($method)) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            break;
        case 'PUT':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
    }
    
    $response = curl_exec($ch);
    
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'error' => $error,
            'status_code' => 0,
            'headers' => '',
            'body' => ''
        ];
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    curl_close($ch);
    
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    
    return [
        'status_code' => $httpCode,
        'headers' => $headers,
        'body' => $body,
        'url' => $url
    ];
}

// Function to format response for display
function formatResult($endpoint, $result, $expected = [200])
{
    $status = $result['status_code'];
    $success = in_array($status, $expected);
    $statusText = $success ? '✅ PASS' : '❌ FAIL';
    
    echo sprintf(
        "%-60s %-10s [%d] %s\n",
        $endpoint,
        $statusText,
        $status,
        $success ? 'OK' : 'ERROR'
    );
    
    // Show first 200 chars of response for failed requests
    if (!$success && !empty($result['body'])) {
        $preview = substr(strip_tags($result['body']), 0, 200);
        echo "   └─ " . $preview . (strlen($preview) >= 200 ? '...' : '') . "\n\n";
    }
    
    return $success;
}

echo "=============================================================\n";
echo "🚀 COMPREHENSIVE ENDPOINT TESTING - " . date('Y-m-d H:i:s') . "\n";
echo "🌍 Base URL: $baseUrl\n";
echo "=============================================================\n\n";

$totalTests = 0;
$passedTests = 0;

// ===========================
// 1. API Endpoints Testing
// ===========================
echo "📡 API ENDPOINTS\n";
echo "=============================================================\n";

$apiEndpoints = [
    // Core API endpoints
    '/api/BrowseProduct' => ['GET'],
    '/api/products' => ['GET'],
    '/api/brands' => ['GET'],
    '/api/categories' => ['GET'],
    '/api/sub-categories' => ['GET'],
    '/api/bundlings' => ['GET'],
    '/api/search-suggestions' => ['GET'],
    '/api/brands-premiere' => ['GET'],
    
    // Regional data
    '/api/regions/provinces' => ['GET'],
    '/api/regions/regencies/11' => ['GET'], // Test with Jakarta province ID
    '/api/regions/districts/1101' => ['GET'], // Test with sample regency ID
    
    // Individual resource endpoints (test with ID 1 if exists)
    '/api/product/1' => ['GET'],
    '/api/brand/1' => ['GET'], 
    '/api/category/1' => ['GET'],
    '/api/bundling/1' => ['GET'],
    '/api/sub-categories/1' => ['GET'],
    
    // Import/Export status endpoints
    '/api/import/queue-status' => ['GET'],
    '/api/google-sheet-export' => ['GET'],
];

foreach ($apiEndpoints as $endpoint => $methods) {
    foreach ($methods as $method) {
        $totalTests++;
        $result = testEndpoint($baseUrl . $endpoint, $method);
        
        // API endpoints should return 200 or 404 (for missing resources)
        $expectedCodes = ($method === 'GET' && strpos($endpoint, '/1') !== false) 
            ? [200, 404] : [200];
            
        if (formatResult($endpoint . " ($method)", $result, $expectedCodes)) {
            $passedTests++;
        }
    }
}

echo "\n";

// ===========================
// 2. Admin Panel Routes Testing
// ===========================
echo "🔐 ADMIN PANEL ENDPOINTS\n";
echo "=============================================================\n";

$adminEndpoints = [
    // Auth routes (should redirect to login or show login form)
    '/admin/login' => [200, 302],
    '/admin/register' => [200, 302],
    
    // Protected admin routes (should redirect to login)
    '/admin' => [200, 302],
    '/admin/products' => [302], // Should redirect to login
    '/admin/customers' => [302],
    '/admin/transactions' => [302],
    '/admin/brands' => [302],
    '/admin/categories' => [302],
    '/admin/bundlings' => [302],
    '/admin/unified-inventory' => [302],
    '/admin/users' => [302],
    
    // Resource pages
    '/admin/product-photos' => [302],
    '/admin/customer-photos' => [302],
    '/admin/bundling-photos' => [302],
    '/admin/activitylogs' => [302],
    '/admin/api-keys' => [302],
];

foreach ($adminEndpoints as $endpoint => $expectedCodes) {
    $totalTests++;
    $result = testEndpoint($baseUrl . $endpoint, 'GET');
    
    if (formatResult($endpoint, $result, $expectedCodes)) {
        $passedTests++;
    }
}

echo "\n";

// ===========================
// 3. Public Routes Testing
// ===========================
echo "🌐 PUBLIC ENDPOINTS\n";
echo "=============================================================\n";

$publicEndpoints = [
    '/' => [200, 302], // Main page
    '/register' => [200],
    '/forgot-password' => [200],
    '/search-products' => [200],
    '/customers' => [200, 302], // May require auth
    '/pdf/1' => [200, 404], // PDF generation
];

foreach ($publicEndpoints as $endpoint => $expectedCodes) {
    $totalTests++;
    $result = testEndpoint($baseUrl . $endpoint, 'GET');
    
    if (formatResult($endpoint, $result, $expectedCodes)) {
        $passedTests++;
    }
}

echo "\n";

// ===========================
// 4. Special Endpoints Testing
// ===========================
echo "⚡ SPECIAL ENDPOINTS\n";
echo "=============================================================\n";

$specialEndpoints = [
    // File storage/assets
    '/storage/app/public' => [200, 403, 404], // Storage access
    '/livewire/livewire.js' => [200], // Livewire assets
    
    // API utilities
    '/sanctum/csrf-cookie' => [204], // CSRF cookie
    '/up' => [200], // Health check
    
    // Filament assets
    '/filament/exports/1/download' => [200, 404], // Export download
];

foreach ($specialEndpoints as $endpoint => $expectedCodes) {
    $totalTests++;
    $result = testEndpoint($baseUrl . $endpoint, 'GET');
    
    if (formatResult($endpoint, $result, $expectedCodes)) {
        $passedTests++;
    }
}

// ===========================
// 5. API Data Validation Testing
// ===========================
echo "\n🔍 API DATA VALIDATION\n";
echo "=============================================================\n";

// Test key endpoints for proper JSON structure
$apiValidationEndpoints = [
    '/api/products',
    '/api/brands', 
    '/api/categories',
    '/api/bundlings',
    '/api/BrowseProduct',
    '/api/search-suggestions',
];

foreach ($apiValidationEndpoints as $endpoint) {
    $totalTests++;
    $result = testEndpoint($baseUrl . $endpoint, 'GET');
    
    $validJson = false;
    $hasData = false;
    
    if ($result['status_code'] == 200) {
        $jsonData = json_decode($result['body'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $validJson = true;
            // Check if data exists (either direct array or has 'data' key)
            $hasData = !empty($jsonData) && (
                is_array($jsonData) || 
                (isset($jsonData['data']) && !empty($jsonData['data']))
            );
        }
    }
    
    $success = $result['status_code'] == 200 && $validJson;
    $statusText = $success ? '✅ JSON' : '❌ FAIL';
    $dataText = $hasData ? '📊 HAS-DATA' : '📭 NO-DATA';
    
    echo sprintf(
        "%-50s %-10s %-12s [%d]\n",
        $endpoint . ' (JSON)',
        $statusText,
        $dataText,
        $result['status_code']
    );
    
    if ($success) {
        $passedTests++;
    }
}

// ===========================
// Summary Results
// ===========================
echo "\n=============================================================\n";
echo "📊 TEST SUMMARY\n";
echo "=============================================================\n";

$failedTests = $totalTests - $passedTests;
$successRate = ($totalTests > 0) ? round(($passedTests / $totalTests) * 100, 2) : 0;

echo "Total Tests:    $totalTests\n";
echo "Passed:         $passedTests ✅\n"; 
echo "Failed:         $failedTests ❌\n";
echo "Success Rate:   $successRate%\n\n";

if ($successRate >= 80) {
    echo "🎉 EXCELLENT! Most endpoints are working properly.\n";
} elseif ($successRate >= 60) {
    echo "⚠️  GOOD! Some issues found, but most endpoints work.\n";
} else {
    echo "🚨 ATTENTION! Multiple endpoints have issues - needs investigation.\n";
}

echo "\n💡 NOTE: 302 redirects for admin routes are expected (authentication).\n";
echo "💡 NOTE: 404 errors for specific resource IDs are expected if resources don't exist.\n";
echo "\nTesting completed at " . date('Y-m-d H:i:s') . "\n";

?>
