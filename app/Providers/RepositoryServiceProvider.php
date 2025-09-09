<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\Transaction;
use App\Repositories\ProductRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repositories as singletons for better performance
        $this->app->singleton(ProductRepository::class, function ($app) {
            return new ProductRepository(new Product());
        });
        
        $this->app->singleton(TransactionRepository::class, function ($app) {
            return new TransactionRepository(new Transaction());
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Optimize database configurations
        $this->optimizeDatabaseConfiguration();
        
        // Set up query optimization
        $this->optimizeQueryPerformance();
        
        // Configure schema settings
        $this->configureSchemaSettings();
    }
    
    /**
     * Optimize database configuration for better performance
     */
    private function optimizeDatabaseConfiguration(): void
    {
        // Enable strict mode for better data integrity (production only)
        if (app()->environment('production')) {
            config(['database.connections.mysql.strict' => true]);
        }
        
        // Set connection pool settings
        if (config('database.connections.mysql.options')) {
            config([
                'database.connections.mysql.options' => array_merge(
                    config('database.connections.mysql.options', []),
                    [
                        \PDO::ATTR_PERSISTENT => true, // Enable persistent connections
                        \PDO::ATTR_EMULATE_PREPARES => false, // Use native prepared statements
                        \PDO::ATTR_STRINGIFY_FETCHES => false, // Keep numeric types
                        \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true, // Buffer query results
                    ]
                )
            ]);
        }
    }
    
    /**
     * Optimize query performance monitoring
     */
    private function optimizeQueryPerformance(): void
    {
        // Enable query logging in development
        if (app()->environment('local', 'development')) {
            DB::enableQueryLog();
        }
        
        // Monitor slow queries in production
        if (app()->environment('production')) {
            DB::listen(function ($query) {
                if ($query->time > config('app.slow_query_threshold', 1000)) { // 1 second default
                    Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms',
                        'connection' => $query->connection->getName(),
                    ]);
                }
            });
        }
        
        // Set up connection retry logic
        $this->setupConnectionRetry();
    }
    
    /**
     * Configure schema settings for better performance
     */
    private function configureSchemaSettings(): void
    {
        // Set default string length for older MySQL versions
        Schema::defaultStringLength(191);
        
        // Configure morphMap for better polymorphic performance
        $this->configureMorphMap();
    }
    
    /**
     * Configure morph map for polymorphic relationships
     */
    private function configureMorphMap(): void
    {
        // This helps avoid storing full class names in database
        // which improves query performance and allows for refactoring
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap([
            'product' => \App\Models\Product::class,
            'transaction' => \App\Models\Transaction::class,
            'customer' => \App\Models\Customer::class,
            'category' => \App\Models\Category::class,
            'brand' => \App\Models\Brand::class,
            'bundling' => \App\Models\Bundling::class,
        ]);
    }
    
    /**
     * Setup connection retry logic for better reliability
     */
    private function setupConnectionRetry(): void
    {
        // This helps handle connection drops and temporary network issues
        DB::reconnect();
        
        // Set connection timeout (if using Redis)
        if (config('cache.default') === 'redis') {
            config(['database.redis.options.read_timeout' => 60]);
            config(['database.redis.options.tcp_keepalive' => 1]);
        }
        
        // Add event listener for connection errors
        DB::listen(function ($query) {
            if (str_contains($query->sql, 'Lock wait timeout exceeded')) {
                Log::warning('Lock wait timeout detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                    'connection' => $query->connection->getName(),
                ]);
            }
        });
        
        // Configure retry mechanism for database operations
        app()->singleton('db.retry', function () {
            return new class {
                public function run(\Closure $callback, $maxAttempts = 3, $sleepMs = 100)
                {
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
                            ]);
                        }
                    }
                }
            };
        });
    }
    
    /**
     * Optimize memory usage for large datasets
     */
    private function optimizeMemoryUsage(): void
    {
        // Set memory limit for CLI commands
        if (app()->runningInConsole()) {
            ini_set('memory_limit', config('app.cli_memory_limit', '512M'));
        }
        
        // Configure chunk size for large operations
        config(['database.chunk_size' => config('app.database_chunk_size', 1000)]);
    }
    
    /**
     * Setup performance monitoring hooks
     */
    private function setupPerformanceMonitoring(): void
    {
        // Monitor memory usage
        register_shutdown_function(function () {
            if (app()->environment('local', 'development')) {
                $memoryUsage = memory_get_peak_usage(true);
                $formattedMemory = number_format($memoryUsage / 1024 / 1024, 2);
                Log::debug("Peak memory usage: {$formattedMemory} MB");
            }
        });
        
        // Monitor query counts in development
        if (app()->environment('local', 'development')) {
            app()->terminating(function () {
                $queries = DB::getQueryLog();
                if (count($queries) > config('app.query_count_warning', 50)) {
                    Log::warning('High query count detected', [
                        'query_count' => count($queries),
                        'request_uri' => request()->getRequestUri(),
                    ]);
                }
            });
        }
    }
}
