<?php

use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ImportStatusController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\SubCategoryController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\AdvancedSearchController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Admin\WhatsAppController;
use App\Http\Controllers\GoogleSheetSyncController;
use App\Http\Controllers\TransactionCheckController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Region API Routes (for dropdown functionality)
Route::prefix('regions')->group(function () {
    Route::get('/provinces', [RegionController::class, 'getProvinces']);
    Route::get('/regencies/{provinceId}', [RegionController::class, 'getRegencies']);
    Route::get('/districts/{regencyId}', [RegionController::class, 'getDistricts']);
    Route::get('/villages/{districtId}', [RegionController::class, 'getVillages']);
});

// WhatsApp API Routes
Route::prefix('server')->group(function () {
    Route::post('/stop', [WhatsAppController::class, 'stopServer']);
});

Route::prefix('sessions')->group(function () {
    Route::post('/{session}/restart', [WhatsAppController::class, 'restartSession']);
    Route::post('/{session}/logout', [WhatsAppController::class, 'logoutSession']);
    Route::post('/{session}/start', [WhatsAppController::class, 'startSession']);
});

Route::post('/sendText', [WhatsAppController::class, 'sendTestMessage']);

// Public API Routes (no authentication required)
Route::middleware(['throttle:120,1'])->group(function () {
    // Product browsing endpoints (public access)
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/category/{category:slug}', [CategoryController::class, 'show']);
    Route::get('/brands', [BrandController::class, 'index']);
    Route::get('/brand/{brand:slug}', [BrandController::class, 'show']);
    Route::get('/brands-premiere', [BrandController::class, 'getPremiereBrands']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/product/{product:slug}', [ProductController::class, 'show']);
    Route::get('/BrowseProduct', [ProductController::class, 'ProductsHome']);
    Route::get('/bundlings', [\App\Http\Controllers\Api\BundlingController::class, 'index']);
    Route::get('/bundling/{bundling:slug}', [\App\Http\Controllers\Api\BundlingController::class, 'show']);
    Route::get('/sub-categories', [SubCategoryController::class, 'index']);
    Route::get('/sub-categories/{subCategory:slug}', [SubCategoryController::class, 'show']);
    Route::get('/search-suggestions', [ProductController::class, 'searchSuggestions'])->middleware('throttle:60,1');
    
    // Transactions API for availability checking
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])->middleware('throttle:120,1');
        Route::post('/check-availability', [TransactionController::class, 'checkAvailability'])->middleware('throttle:120,1');
    });
    
    // Advanced Search API
    Route::prefix('search')->group(function () {
        Route::get('/', [AdvancedSearchController::class, 'search'])->middleware('throttle:120,1');
        Route::get('/autocomplete', [AdvancedSearchController::class, 'autocomplete'])->middleware('throttle:180,1');
        Route::get('/popular', [AdvancedSearchController::class, 'popularSuggestions'])->middleware('throttle:60,1');
        Route::get('/stats', [AdvancedSearchController::class, 'getSearchStats'])->middleware('throttle:30,1');
    });
    
    // Availability Checking API
    Route::prefix('availability')->group(function () {
        Route::post('/check', [AvailabilityController::class, 'check'])->middleware('throttle:120,1');
        Route::post('/check-multiple', [AvailabilityController::class, 'checkMultiple'])->middleware('throttle:60,1');
        Route::get('/unavailable-dates', [AvailabilityController::class, 'getUnavailableDates'])->middleware('throttle:180,1');
        Route::post('/check-range', [AvailabilityController::class, 'isDateRangeAvailable'])->middleware('throttle:120,1');
        Route::post('/check-cart', [AvailabilityController::class, 'checkCartAvailability'])->middleware('throttle:30,1');
        Route::get('/stats', [AvailabilityController::class, 'getStats'])->middleware('throttle:30,1');
    });
});

// Protected API Routes (require API key)
Route::middleware(['api_key', 'throttle:60,1'])->group(function () {
    // Write operations require authentication
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
    
    Route::post('/brands', [BrandController::class, 'store']);
    Route::put('/brands/{brand}', [BrandController::class, 'update']);
    Route::delete('/brands/{brand}', [BrandController::class, 'destroy']);
    
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);
    
    Route::post('/bundlings', [\App\Http\Controllers\Api\BundlingController::class, 'store']);
    Route::put('/bundlings/{bundling}', [\App\Http\Controllers\Api\BundlingController::class, 'update']);
    Route::delete('/bundlings/{bundling}', [\App\Http\Controllers\Api\BundlingController::class, 'destroy']);
    
    Route::post('/sub-categories', [SubCategoryController::class, 'store']);
    Route::put('/sub-categories/{sub_category}', [SubCategoryController::class, 'update']);
    Route::delete('/sub-categories/{sub_category}', [SubCategoryController::class, 'destroy']);
    
    // Sync and transaction operations
    Route::post('/google-sheet-sync', [GoogleSheetSyncController::class, 'sync']);
    Route::get('/google-sheet-export', [GoogleSheetSyncController::class, 'export']);
    Route::apiResource('/transactions-check', TransactionCheckController::class);
    Route::post('/transaction', [TransactionController::class, 'store']);
    Route::post('/check-transaction', [TransactionController::class, 'DetailTransaction']);
});

// Import Status API Routes (for checking async import progress)
Route::prefix('import')->group(function () {
    Route::get('status/{importId}', [ImportStatusController::class, 'getStatus']);
    Route::get('results/{importId}', [ImportStatusController::class, 'getResults']);
    Route::get('queue-status', [ImportStatusController::class, 'checkQueueStatus']);
});
