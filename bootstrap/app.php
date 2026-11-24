<?php

use App\Http\Middleware\FrontApiKey;
use App\Http\Middleware\WhatsAppAuth;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\PerformanceMonitoring;
use App\Http\Middleware\OptimizedSessionHandler;
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
        // TLS terminates at the host nginx, so the app only sees plain HTTP from
        // ingress-nginx. Without this every URL() renders http:// and session
        // cookies lose the Secure flag.
        $middleware->trustProxies(at: ['10.42.0.0/16', '127.0.0.1']);

        $middleware->alias([
            'api_key' => FrontApiKey::class,
            'whatsapp.auth' => WhatsAppAuth::class
        ]);
        
        $middleware->append(SecurityHeaders::class);
        
        // Temporarily disable performance monitoring for debugging login issues
        // $middleware->append(PerformanceMonitoring::class);
        
        // Temporarily disable optimized session handling due to Redis extension issue
        // $middleware->append(OptimizedSessionHandler::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
