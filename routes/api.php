<?php

use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ImportStatusController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RegionController;
use App\Http\Controllers\Api\SubCategoryController;
use App\Http\Controllers\Api\TransactionController;
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

Route::middleware(['api_key', 'throttle:60,1'])->group(function () {
    Route::get('/product/{product:slug}', [ProductController::class, 'show']);
    Route::get('/search-suggestions', [ProductController::class, 'searchSuggestions'])->middleware('throttle:30,1');
    Route::get('/category/{category:slug}', [CategoryController::class, 'show']);
    Route::apiResource('/categories', CategoryController::class);
    Route::get('/sub-categories/{subCategory:slug}', [SubCategoryController::class, 'show']);
    Route::apiResource('/sub-categories', SubCategoryController::class);
    Route::get('/brand/{brand:slug}', [BrandController::class, 'show']);
    Route::apiResource('/brands', BrandController::class);
    Route::get('/brands-premiere', [BrandController::class, 'getPremiereBrands']);
    Route::post('/google-sheet-sync', [GoogleSheetSyncController::class, 'sync']);
    Route::get('/google-sheet-export', [GoogleSheetSyncController::class, 'export']);
    // Bundling routes (slug based, RESTful)
    Route::get('/bundling/{bundling:slug}', [\App\Http\Controllers\Api\BundlingController::class, 'show']);
    Route::apiResource('/bundlings', \App\Http\Controllers\Api\BundlingController::class)->parameters([
        'bundlings' => 'bundling:slug',
    ]);
    Route::apiResource('/products', ProductController::class);
    Route::get('/BrowseProduct', [ProductController::class, 'ProductsHome']);
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
