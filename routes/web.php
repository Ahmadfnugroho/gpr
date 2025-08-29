<?php

use App\Http\Controllers\GoogleSheetSyncController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SessionController;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

Route::get('/', [SessionController::class, 'index']);
Route::post('/', [SessionController::class, 'login']);

// Reset Password Routes
Route::get('/forgot-password', [SessionController::class, 'showForgotPasswordForm'])->name('forgot.password');
Route::post('/forgot-password', [SessionController::class, 'resetPassword'])->name('reset.password');

Route::get('pdf/{order}', PdfController::class)->name('pdf');
Route::get('/auth/google/callback', [GoogleSheetSyncController::class, 'handleCallback'])->name('google.callback');
Route::get('/sync', [GoogleSheetSyncController::class, 'sync'])->name('sync');

// Registration Routes
Route::get('/register', [RegistrationController::class, 'showForm'])->name('registration.form');
Route::post('/register', [RegistrationController::class, 'register'])->name('registration.store');
Route::get('/registration/success', [RegistrationController::class, 'success'])->name('registration.success');
Route::get('/email/verify/{id}/{hash}', [RegistrationController::class, 'verifyEmail'])
    ->middleware(['signed'])
    ->name('verification.verify');
