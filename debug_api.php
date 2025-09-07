<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== DEBUG API RELATIONSHIPS ===" . PHP_EOL;

// Test Brand 1 (Apple)
$brand = \App\Models\Brand::find(1);
echo "Brand: " . $brand->name . PHP_EOL;
echo "Products count (direct query): " . $brand->products()->count() . PHP_EOL;
echo "Products count (withCount): " . \App\Models\Brand::withCount('products')->find(1)->products_count . PHP_EOL;

// Test if products are being loaded
$brandWithProducts = \App\Models\Brand::with('products')->find(1);
echo "Products loaded with with(): " . $brandWithProducts->products->count() . PHP_EOL;

// Check a few product names
if ($brandWithProducts->products->count() > 0) {
    echo "First product: " . $brandWithProducts->products->first()->name . PHP_EOL;
    echo "Product brand_id: " . $brandWithProducts->products->first()->brand_id . PHP_EOL;
}

// Test the actual API resource output
echo PHP_EOL . "=== BRAND RESOURCE DEBUG ===" . PHP_EOL;
$brandResource = new \App\Http\Resources\Api\BrandResource($brandWithProducts);
$resourceArray = $brandResource->toArray(request());
echo "Brand resource products count: " . count($resourceArray['products']) . PHP_EOL;
echo "Brand resource products_count: " . $resourceArray['products_count'] . PHP_EOL;

echo PHP_EOL . "=== CATEGORIES DEBUG ===" . PHP_EOL;

// Check table structure
try {
    $subCatModel = new \App\Models\SubCategory();
    echo "SubCategory table: " . $subCatModel->getTable() . PHP_EOL;
    
    // Check if sub_categories table exists and what columns it has
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('sub_categories');
    echo "sub_categories columns: " . implode(', ', $columns) . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error checking sub_categories table: " . $e->getMessage() . PHP_EOL;
}

try {
    // Try without subCategories count first
    $categories = \App\Models\Category::withCount('products')->get();
    echo "Categories loaded successfully (without subCategories count): " . $categories->count() . PHP_EOL;
    
    if ($categories->count() > 0) {
        $first = $categories->first();
        echo "First category: " . $first->name . PHP_EOL;
        echo "Products count: " . $first->products_count . PHP_EOL;
    }
    
    // Now try with subCategories count
    $categoriesWithSub = \App\Models\Category::withCount(['products', 'subCategories'])->get();
    echo "Categories with subCategories count: " . $categoriesWithSub->count() . PHP_EOL;
    
} catch (Exception $e) {
    echo "Error loading categories: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace:" . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
}
