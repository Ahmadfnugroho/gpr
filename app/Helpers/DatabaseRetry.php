<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class DatabaseRetry
{
    /**
     * Execute a database operation with automatic retry on lock timeout
     *
     * @param \Closure $callback The database operation to execute
     * @param int $maxAttempts Maximum number of retry attempts
     * @param int $sleepMs Initial sleep time in milliseconds (will increase with exponential backoff)
     * @return mixed The result of the callback
     * @throws \Exception If all retry attempts fail
     */
    public static function run(\Closure $callback, int $maxAttempts = 3, int $sleepMs = 100)
    {
        if (app()->has('db.retry')) {
            return app('db.retry')->run($callback, $maxAttempts, $sleepMs);
        }
        
        // Fallback implementation if the service is not registered
        $attempts = 0;
        
        while ($attempts < $maxAttempts) {
            try {
                return $callback();
            } catch (\Exception $e) {
                $attempts++;
                
                // Only retry on lock timeout errors
                if (!str_contains($e->getMessage(), 'Lock wait timeout exceeded') || $attempts >= $maxAttempts) {
                    throw $e;
                }
                
                // Exponential backoff
                $sleepTime = $sleepMs * pow(2, $attempts - 1);
                usleep($sleepTime * 1000);
                
                Log::info('Retrying database operation after lock timeout', [
                    'attempt' => $attempts,
                    'max_attempts' => $maxAttempts,
                    'sleep_ms' => $sleepTime,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}