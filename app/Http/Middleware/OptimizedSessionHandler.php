<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Helpers\DatabaseRetry;

class OptimizedSessionHandler
{
    /**
     * Handle an incoming request with optimized session handling.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply optimizations for routes that need session
        if ($this->shouldOptimizeSession($request)) {
            // Apply session optimizations before processing the request
            $this->optimizeSessionBefore($request);
            
            // Process the request
            $response = $next($request);
            
            // Apply session optimizations after processing the request
            $this->optimizeSessionAfter($request, $response);
            
            return $response;
        }
        
        return $next($request);
    }
    
    /**
     * Determine if session optimization should be applied.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function shouldOptimizeSession(Request $request): bool
    {
        // Skip for API routes or routes that don't need session
        if ($request->is('api/*')) {
            return false;
        }
        
        // Skip for specific routes that don't need session
        $excludedPaths = [
            'assets/*',
            'images/*',
            'js/*',
            'css/*',
            'fonts/*',
        ];
        
        foreach ($excludedPaths as $path) {
            if ($request->is($path)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Apply session optimizations before processing the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function optimizeSessionBefore(Request $request): void
    {
        // Use retry logic for session read operations
        if ($request->hasSession() && $request->session()->isStarted()) {
            try {
                // Use retry logic for critical session operations
                DatabaseRetry::run(function() use ($request) {
                    // Force session data to be loaded
                    $request->session()->all();
                    return true;
                });
            } catch (\Exception $e) {
                Log::error('Session read error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }
    
    /**
     * Apply session optimizations after processing the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $response
     * @return void
     */
    protected function optimizeSessionAfter(Request $request, $response): void
    {
        // Only apply for started sessions
        if ($request->hasSession() && $request->session()->isStarted()) {
            try {
                // Use retry logic for session write operations
                DatabaseRetry::run(function() use ($request) {
                    // Save session data with retry logic
                    $request->session()->save();
                    return true;
                });
            } catch (\Exception $e) {
                Log::error('Session write error', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }
}