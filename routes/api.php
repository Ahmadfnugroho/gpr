<?php

use App\Http\Controllers\Api\BrandController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SubCategoryController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\GoogleSheetSyncController;
use App\Http\Controllers\TransactionCheckController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
});
// ->middleware('auth:sanctum');

Route::middleware('api_key')->group(function () {


    Route::get('/category/{category:slug}', [CategoryController::class, 'show']);
    Route::apiResource('/categories', CategoryController::class);

    Route::get('/subCategory/{subCategory:slug}', [SubCategoryController::class, 'show']);
    Route::apiResource('/sub-categories', SubCategoryController::class);

    Route::get('/brand/{brand:slug}', [BrandController::class, 'show']);
    Route::apiResource('/brands', BrandController::class);
    Route::get('/brands-premiere', [BrandController::class, 'getPremiereBrands']);

    Route::post('/google-sheet-sync', [GoogleSheetSyncController::class, 'sync']);


    Route::get('/product/{product:slug}', [ProductController::class, 'show']);
    Route::apiResource('/products', ProductController::class);
    Route::get('/BrowseProduct', [ProductController::class, 'ProductsHome']);

    Route::apiResource('/transactions-check', TransactionCheckController::class);


    Route::post('/transaction', [TransactionController::class, 'store']);

    Route::post('/check-transaction', [TransactionController::class, 'DetailTransaction']);

    Route::post('/test-upload', function (Illuminate\Http\Request $request) {
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('categories', 'public');
            return response()->json(['path' => $path]);
        }
        return response()->json(['error' => 'No file uploaded'], 400);
    });
});
