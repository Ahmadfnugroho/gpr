<?php

namespace App\Console\Commands;

use App\Services\ResourceCacheService;
use Illuminate\Console\Command;

class OptimizeResourceCache extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'resource:cache-optimize {action=warmup : The action to perform (warmup, clear, stats)}';

    /**
     * The console command description.
     */
    protected $description = 'Optimize resource caching for better performance';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'warmup':
                return $this->warmUpCache();
                
            case 'clear':
                return $this->clearCache();
                
            case 'stats':
                return $this->showStats();
                
            default:
                $this->error('Invalid action. Use: warmup, clear, or stats');
                return Command::FAILURE;
        }
    }
    
    /**
     * Warm up the cache
     */
    private function warmUpCache(): int
    {
        $this->info('Warming up resource caches...');
        
        try {
            $startTime = microtime(true);
            
            ResourceCacheService::warmUpCache();
            
            $executionTime = microtime(true) - $startTime;
            
            $this->info("Cache warm-up completed in {$executionTime} seconds");
            
            // Show cache stats after warmup
            $this->showStats();
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cache warm-up failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Clear all resource caches
     */
    private function clearCache(): int
    {
        $this->info('Clearing all resource caches...');
        
        try {
            ResourceCacheService::clearAllResourceCaches();
            
            $this->info('All resource caches cleared successfully');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cache clear failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Show cache statistics
     */
    private function showStats(): int
    {
        try {
            $stats = ResourceCacheService::getCacheStats();
            
            $this->info('Resource Cache Statistics:');
            $this->line('');
            
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Cache Driver', $stats['cache_driver']],
                    ['Memory Usage', $this->formatBytes($stats['memory_usage'])],
                    ['Peak Memory', $this->formatBytes($stats['peak_memory'])],
                ]
            );
            
            // Redis specific stats
            if (isset($stats['redis_memory'])) {
                $this->line('');
                $this->info('Redis Statistics:');
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Redis Memory', $stats['redis_memory']],
                        ['Redis Keys', $stats['redis_keys'] ?? 'N/A'],
                    ]
                );
            }
            
            if (isset($stats['redis_error'])) {
                $this->warn('Redis Error: ' . $stats['redis_error']);
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to get cache stats: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
