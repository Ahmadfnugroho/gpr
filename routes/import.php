<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportErrorController;

/*
|--------------------------------------------------------------------------
| Import Error Routes
|--------------------------------------------------------------------------
|
| Routes for handling import errors, viewing error details, and downloading
| failed rows for correction and re-import.
|
*/

Route::prefix('import')->name('import.')->group(function () {
    
    // Download failed rows as Excel file
    Route::get('/download-failed', [ImportErrorController::class, 'downloadFailedRows'])
        ->name('download-failed');
    
    // View all import errors in detailed view
    Route::get('/view-errors', [ImportErrorController::class, 'viewAllErrors'])
        ->name('view-errors');
    
    // Get import statistics (AJAX)
    Route::get('/statistics', [ImportErrorController::class, 'getImportStatistics'])
        ->name('statistics');
    
    // Get failed rows with pagination (AJAX)
    Route::get('/failed-rows', [ImportErrorController::class, 'getFailedRowsPaginated'])
        ->name('failed-rows');
    
    // Clear failed import data from session
    Route::delete('/clear-failed-data', [ImportErrorController::class, 'clearFailedImportData'])
        ->name('clear-failed-data');
        
    // Re-process specific failed rows
    Route::post('/reprocess-failed', [ImportErrorController::class, 'reprocessFailedRows'])
        ->name('reprocess-failed');
});
