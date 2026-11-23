<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class FrontApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-KEY');
        
        if (!$apiKey) {
            return response()->json([
                'message' => 'API key required',
                'error' => 'Missing X-API-KEY header',
                'hint' => 'Add X-API-KEY header with a valid API key'
            ], 401);
        }
        
        $keyRecord = Cache::remember('api_key:' . hash('sha256', $apiKey), 60, function () use ($apiKey) {
            return ApiKey::where('key', $apiKey)->first();
        });
        
        if (!$keyRecord) {
            return response()->json([
                'message' => 'Invalid API key',
                'error' => 'API key not found'
            ], 401);
        }
        
        if (!$keyRecord->active) {
            return response()->json([
                'message' => 'API key inactive',
                'error' => 'This API key has been deactivated'
            ], 401);
        }
        
        if ($keyRecord->expires_at && $keyRecord->expires_at->isPast()) {
            return response()->json([
                'message' => 'API key expired',
                'error' => 'This API key expired on ' . $keyRecord->expires_at->format('Y-m-d')
            ], 401);
        }
        
        return $next($request);
    }
}
