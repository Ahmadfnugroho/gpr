<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoring
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip monitoring for static assets or file requests
        if ($this->shouldSkipMonitoring($request)) {
            return $next($request);
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Skip if query logging might cause issues
        $initialQueryCount = 0;
        try {
            DB::enableQueryLog();
            $initialQueryCount = count(DB::getQueryLog());
        } catch (\Exception $e) {
            // Continue without query logging if it fails
            Log::warning('Query logging failed in performance monitoring', ['error' => $e->getMessage()]);
        }

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $queries = DB::getQueryLog();
        
        $metrics = [
            'timestamp' => now()->toISOString(),
            'method' => $request->method(),
            'uri' => $request->getPathInfo(),
            'status_code' => $response->getStatusCode(),
            'response_time' => round(($endTime - $startTime) * 1000, 2), // milliseconds
            'memory_usage' => $this->formatBytes($endMemory - $startMemory),
            'peak_memory' => $this->formatBytes(memory_get_peak_usage(true)),
            'query_count' => count($queries) - $initialQueryCount,
            'query_time' => $this->calculateQueryTime($queries, $initialQueryCount),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
        ];

        // Log performance data
        $this->logPerformanceMetrics($metrics);

        // Store metrics for dashboard/reporting
        $this->storeMetrics($metrics);

        // Add performance headers to response
        $response->headers->set('X-Response-Time', $metrics['response_time'] . 'ms');
        $response->headers->set('X-Query-Count', $metrics['query_count']);
        $response->headers->set('X-Memory-Usage', $metrics['memory_usage']);

        // Alert on slow requests
        if ($metrics['response_time'] > config('app.slow_request_threshold', 2000)) {
            $this->alertSlowRequest($metrics, $queries);
        }

        // Alert on high query count
        if ($metrics['query_count'] > config('app.high_query_threshold', 20)) {
            $this->alertHighQueryCount($metrics, $queries);
        }

        return $response;
    }

    /**
     * Determine if monitoring should be skipped for this request
     */
    private function shouldSkipMonitoring(Request $request): bool
    {
        $skipPatterns = [
            '/favicon.ico',
            '/robots.txt',
            '*.css',
            '*.js',
            '*.png',
            '*.jpg',
            '*.jpeg',
            '*.gif',
            '*.svg',
            '*.ico',
            '*.woff',
            '*.woff2',
            '*.ttf',
            '*.eot',
            '/storage/*',
            '/filament/assets/*'
        ];

        $path = $request->getPathInfo();
        
        foreach ($skipPatterns as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate total query execution time
     */
    private function calculateQueryTime(array $queries, int $initialCount): float
    {
        $totalTime = 0;
        $relevantQueries = array_slice($queries, $initialCount);
        
        foreach ($relevantQueries as $query) {
            $totalTime += $query['time'] ?? 0;
        }
        
        return round($totalTime, 2);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Log performance metrics
     */
    private function logPerformanceMetrics(array $metrics): void
    {
        // Only log if response time is above threshold or in debug mode
        $shouldLog = $metrics['response_time'] > config('app.log_performance_threshold', 1000) 
                    || config('app.debug', false);

        if ($shouldLog) {
            Log::channel('performance')->info('Request Performance', $metrics);
        }
    }

    /**
     * Store metrics for dashboard/reporting
     */
    private function storeMetrics(array $metrics): void
    {
        $key = 'performance_metrics:' . date('Y-m-d-H');
        
        // Store hourly aggregated metrics
        $existing = Cache::get($key, []);
        $existing[] = [
            'timestamp' => $metrics['timestamp'],
            'uri' => $metrics['uri'],
            'method' => $metrics['method'],
            'response_time' => $metrics['response_time'],
            'query_count' => $metrics['query_count'],
            'memory_usage' => $metrics['memory_usage'],
            'status_code' => $metrics['status_code']
        ];

        // Keep only last 1000 entries per hour to prevent memory issues
        if (count($existing) > 1000) {
            $existing = array_slice($existing, -1000);
        }

        Cache::put($key, $existing, now()->addHours(25)); // Keep for 25 hours

        // Update daily statistics
        $this->updateDailyStats($metrics);
    }

    /**
     * Update daily performance statistics
     */
    private function updateDailyStats(array $metrics): void
    {
        $date = date('Y-m-d');
        $statsKey = "daily_performance_stats:{$date}";
        
        $stats = Cache::get($statsKey, [
            'total_requests' => 0,
            'avg_response_time' => 0,
            'max_response_time' => 0,
            'min_response_time' => PHP_INT_MAX,
            'total_response_time' => 0,
            'avg_query_count' => 0,
            'max_query_count' => 0,
            'error_count' => 0,
            'endpoints' => []
        ]);

        $stats['total_requests']++;
        $stats['total_response_time'] += $metrics['response_time'];
        $stats['avg_response_time'] = $stats['total_response_time'] / $stats['total_requests'];
        $stats['max_response_time'] = max($stats['max_response_time'], $metrics['response_time']);
        $stats['min_response_time'] = min($stats['min_response_time'], $metrics['response_time']);
        
        $stats['avg_query_count'] = (($stats['avg_query_count'] * ($stats['total_requests'] - 1)) + $metrics['query_count']) / $stats['total_requests'];
        $stats['max_query_count'] = max($stats['max_query_count'], $metrics['query_count']);

        if ($metrics['status_code'] >= 400) {
            $stats['error_count']++;
        }

        // Track endpoint-specific stats
        $endpoint = $metrics['method'] . ' ' . $metrics['uri'];
        if (!isset($stats['endpoints'][$endpoint])) {
            $stats['endpoints'][$endpoint] = [
                'count' => 0,
                'avg_response_time' => 0,
                'total_response_time' => 0
            ];
        }

        $endpointStats = &$stats['endpoints'][$endpoint];
        $endpointStats['count']++;
        $endpointStats['total_response_time'] += $metrics['response_time'];
        $endpointStats['avg_response_time'] = $endpointStats['total_response_time'] / $endpointStats['count'];

        Cache::put($statsKey, $stats, now()->addDays(8)); // Keep for 8 days
    }

    /**
     * Alert on slow requests
     */
    private function alertSlowRequest(array $metrics, array $queries): void
    {
        $context = [
            'metrics' => $metrics,
            'slow_queries' => array_filter($queries, fn($q) => ($q['time'] ?? 0) > 500)
        ];

        Log::channel('alerts')->warning('Slow Request Detected', $context);

        // Could integrate with external monitoring services here
        // e.g., Sentry, New Relic, DataDog, etc.
    }

    /**
     * Alert on high query count
     */
    private function alertHighQueryCount(array $metrics, array $queries): void
    {
        $context = [
            'metrics' => $metrics,
            'queries' => array_map(fn($q) => [
                'query' => $q['query'] ?? '',
                'time' => $q['time'] ?? 0,
                'bindings' => $q['bindings'] ?? []
            ], $queries)
        ];

        Log::channel('alerts')->warning('High Query Count Detected', $context);

        // Could identify potential N+1 queries or missing eager loading
        $this->analyzeQueries($queries);
    }

    /**
     * Analyze queries for potential issues
     */
    private function analyzeQueries(array $queries): void
    {
        $queryPatterns = [];
        
        foreach ($queries as $query) {
            $sql = $query['query'] ?? '';
            
            // Normalize query by removing parameters
            $normalized = preg_replace('/\?|\d+|\'[^\']*\'/', '?', $sql);
            
            if (!isset($queryPatterns[$normalized])) {
                $queryPatterns[$normalized] = 0;
            }
            $queryPatterns[$normalized]++;
        }

        // Detect potential N+1 queries (same query pattern executed many times)
        foreach ($queryPatterns as $pattern => $count) {
            if ($count > 10) {
                Log::channel('alerts')->warning('Potential N+1 Query Detected', [
                    'pattern' => $pattern,
                    'count' => $count
                ]);
            }
        }
    }
}
