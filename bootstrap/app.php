<?php

use App\Http\Middleware\FrontApiKey;
use App\Http\Middleware\WhatsAppAuth;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\PerformanceMonitoring;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'api_key' => FrontApiKey::class,
            'whatsapp.auth' => WhatsAppAuth::class
        ]);
        
        // Add security headers globally
        $middleware->append(SecurityHeaders::class);
        
        // Add performance monitoring globally (fixed version)
        $middleware->append(PerformanceMonitoring::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
