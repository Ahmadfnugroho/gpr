<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

// TEMPORARY DEBUG ROUTES - Remove in production
if (app()->environment('local')) {
    
    Route::get('/debug/session', function (Request $request) {
        return response()->json([
            'session_id' => $request->session()->getId(),
            'session_name' => $request->session()->getName(),
            'csrf_token' => csrf_token(),
            'session_data' => $request->session()->all(),
            'cookies' => $request->cookies->all(),
            'session_config' => [
                'driver' => config('session.driver'),
                'lifetime' => config('session.lifetime'),
                'domain' => config('session.domain'),
                'path' => config('session.path'),
                'secure' => config('session.secure'),
                'http_only' => config('session.http_only'),
                'same_site' => config('session.same_site'),
                'cookie' => config('session.cookie'),
            ],
            'auth' => [
                'check' => auth()->check(),
                'id' => auth()->id(),
                'user' => auth()->user()?->only(['id', 'name', 'email']),
                'guard' => config('auth.defaults.guard'),
            ],
            'headers' => [
                'host' => $request->getHost(),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
            ],
            'files_exist' => [
                'session_file' => file_exists(storage_path('framework/sessions/' . $request->session()->getId())),
                'session_dir_writable' => is_writable(storage_path('framework/sessions')),
                'session_dir_permissions' => substr(sprintf('%o', fileperms(storage_path('framework/sessions'))), -4),
            ]
        ]);
    });

    Route::post('/debug/login-test', function (Request $request) {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $attempt = auth()->attempt($credentials);
        
        return response()->json([
            'attempt_result' => $attempt,
            'auth_check' => auth()->check(),
            'user' => auth()->user()?->only(['id', 'name', 'email']),
            'session_id_before' => $request->session()->getId(),
            'session_id_after' => session()->getId(),
            'csrf_token' => csrf_token(),
            'session_regenerated' => $request->session()->getId() !== session()->getId(),
        ]);
    });

    Route::get('/debug/clear-session', function (Request $request) {
        $oldSessionId = $request->session()->getId();
        $request->session()->flush();
        $request->session()->regenerate();
        
        return response()->json([
            'message' => 'Session cleared and regenerated',
            'old_session_id' => $oldSessionId,
            'new_session_id' => $request->session()->getId(),
        ]);
    });
}
