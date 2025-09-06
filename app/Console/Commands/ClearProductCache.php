<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearProductCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:clear-cache {--all : Clear all product related caches}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear product availability and search result caches';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing product caches...');
        
        if ($this->option('all')) {
            // Clear all cache
            Cache::flush();
            $this->info('All caches cleared!');
        } else {
            // Clear specific product caches
            $patterns = [
                'product_availability_*',
                'product_status_*', 
                'product_search_details_*',
                'product_serials_*'
            ];
            
            foreach ($patterns as $pattern) {
                $this->clearCacheByPattern($pattern);
            }
            
            $this->info('Product caches cleared!');
        }
        
        return 0;
    }
    
    private function clearCacheByPattern(string $pattern)
    {
        // For Redis cache store
        if (config('cache.default') === 'redis') {
            $redis = Cache::getRedis();
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                $redis->del($keys);
            }
        }
        // For other cache stores, you might need different implementations
    }
}
