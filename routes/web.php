<?php

use App\Http\Controllers\GoogleSheetSyncController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProductSearchController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

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

// Product Search Route
Route::get('/search-products', [ProductSearchController::class, 'index'])->name('product.search');

// Region API Routes
Route::prefix('api/regions')->group(function () {
    Route::get('/provinces', [App\Http\Controllers\Api\RegionController::class, 'getProvinces'])->name('api.provinces');
    Route::get('/regencies/{provinceId}', [App\Http\Controllers\Api\RegionController::class, 'getRegencies'])->name('api.regencies');
    Route::get('/districts/{regencyId}', [App\Http\Controllers\Api\RegionController::class, 'getDistricts'])->name('api.districts');
    Route::get('/villages/{districtId}', [App\Http\Controllers\Api\RegionController::class, 'getVillages'])->name('api.villages');
});

// Admin Panel Redirect
Route::get('/admin-login', function () {
    return redirect('/admin');
});

// WhatsApp Management Routes (protected by auth)
Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
    // Root whatsapp route - redirect to dashboard (will be protected by middleware)
    Route::get('/', function () {
        return redirect()->route('whatsapp.dashboard');
    })->middleware('whatsapp.auth')->name('index');

    // Login routes (no middleware)
    Route::get('/login', function () {
        return view('admin.whatsapp.login');
    })->name('login.form');

    Route::post('/login', function (\Illuminate\Http\Request $request) {
        $username = $request->input('username');
        $password = $request->input('password');

        if ($username === 'wahaadmin' && $password === 'Infrasglobal@100') {
            $request->session()->put('whatsapp_authenticated', true);
            return redirect()->route('whatsapp.dashboard');
        } else {
            return redirect()->back()->withErrors(['Invalid credentials']);
        }
    })->name('login');

    Route::post('/auth-logout', function (\Illuminate\Http\Request $request) {
        $request->session()->forget('whatsapp_authenticated');
        return redirect()->route('whatsapp.dashboard');
    })->name('auth.logout');

    // Protected routes (with middleware)
    Route::middleware('whatsapp.auth')->group(function () {
        Route::get('/dashboard', [App\Http\Controllers\Admin\WhatsAppController::class, 'dashboard'])->name('dashboard');
        Route::get('/status', [App\Http\Controllers\Admin\WhatsAppController::class, 'getSessionStatus'])->name('status');
        Route::get('/qr', [App\Http\Controllers\Admin\WhatsAppController::class, 'getQrCode'])->name('qr');
        Route::post('/test', [App\Http\Controllers\Admin\WhatsAppController::class, 'sendTestMessage'])->name('test');
        Route::post('/restart', [App\Http\Controllers\Admin\WhatsAppController::class, 'restartSession'])->name('restart');
        Route::post('/logout-session', [App\Http\Controllers\Admin\WhatsAppController::class, 'logoutSession'])->name('logout');
        Route::get('/logs', [App\Http\Controllers\Admin\WhatsAppController::class, 'getSessionLogs'])->name('logs');
    });
});
