<?php

use App\Http\Controllers\GoogleSheetSyncController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\SessionController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::get('/', [SessionController::class, 'index']);
Route::post('/', [SessionController::class, 'login']);

Route::get('pdf/{order}', PdfController::class)->name('pdf');
Route::get('/auth/google/callback', [GoogleSheetSyncController::class, 'handleCallback'])->name('google.callback');
Route::get('/sync', [GoogleSheetSyncController::class, 'sync'])->name('sync');
