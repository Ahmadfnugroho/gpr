<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains settings for optimizing application performance,
    | including caching, database queries, and resource management.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    
    'cache' => [
        // Default cache TTL in minutes
        'default_ttl' => env('CACHE_DEFAULT_TTL', 15),
        
        // Long term cache TTL for static data (in minutes)
        'long_ttl' => env('CACHE_LONG_TTL', 60),
        
        // Short term cache TTL for dynamic data (in minutes)
        'short_ttl' => env('CACHE_SHORT_TTL', 5),
        
        // Enable/disable caching globally
        'enabled' => env('CACHE_ENABLED', true),
        
        // Warm up cache on application boot
        'warmup_on_boot' => env('CACHE_WARMUP_ON_BOOT', false),
        
        // Auto-refresh cache for critical data
        'auto_refresh' => env('CACHE_AUTO_REFRESH', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Query Optimization
    |--------------------------------------------------------------------------
    */
    
    'database' => [
        // Slow query threshold in milliseconds
        'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1000),
        
        // Maximum query count warning threshold
        'query_count_warning' => env('DB_QUERY_COUNT_WARNING', 50),
        
        // Default chunk size for large operations
        'chunk_size' => env('DB_CHUNK_SIZE', 1000),
        
        // Enable query optimization hints
        'optimization_hints' => env('DB_OPTIMIZATION_HINTS', true),
        
        // Connection pool settings
        'connection_pool' => [
            'min_connections' => env('DB_POOL_MIN', 1),
            'max_connections' => env('DB_POOL_MAX', 10),
            'idle_timeout' => env('DB_POOL_IDLE_TIMEOUT', 300),
        ],
        
        // Read/write splitting
        'read_write_split' => env('DB_READ_WRITE_SPLIT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Optimization
    |--------------------------------------------------------------------------
    */
    
    'resources' => [
        // Default pagination settings
        'pagination' => [
            'default_per_page' => env('RESOURCE_DEFAULT_PER_PAGE', 25),
            'max_per_page' => env('RESOURCE_MAX_PER_PAGE', 100),
            'options' => [10, 25, 50, 100],
        ],
        
        // Eager loading optimization
        'eager_loading' => [
            'enabled' => env('RESOURCE_EAGER_LOADING', true),
            'max_depth' => env('RESOURCE_EAGER_LOADING_DEPTH', 3),
        ],
        
        // Defer loading for heavy resources
        'defer_loading' => env('RESOURCE_DEFER_LOADING', true),
        
        // Auto-polling settings
        'polling' => [
            'enabled' => env('RESOURCE_POLLING_ENABLED', true),
            'interval' => env('RESOURCE_POLLING_INTERVAL', '60s'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Management
    |--------------------------------------------------------------------------
    */
    
    'memory' => [
        // CLI memory limit
        'cli_limit' => env('MEMORY_CLI_LIMIT', '512M'),
        
        // Web memory limit
        'web_limit' => env('MEMORY_WEB_LIMIT', '256M'),
        
        // Memory usage warning threshold (in MB)
        'warning_threshold' => env('MEMORY_WARNING_THRESHOLD', 200),
        
        // Enable memory monitoring
        'monitoring' => env('MEMORY_MONITORING', true),
        
        // Garbage collection optimization
        'gc_optimization' => env('MEMORY_GC_OPTIMIZATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | File and Storage Optimization
    |--------------------------------------------------------------------------
    */
    
    'storage' => [
        // Enable file compression
        'compression' => env('STORAGE_COMPRESSION', true),
        
        // Image optimization settings
        'image_optimization' => [
            'enabled' => env('IMAGE_OPTIMIZATION', true),
            'quality' => env('IMAGE_QUALITY', 85),
            'max_width' => env('IMAGE_MAX_WIDTH', 1920),
            'max_height' => env('IMAGE_MAX_HEIGHT', 1080),
        ],
        
        // CDN settings
        'cdn' => [
            'enabled' => env('CDN_ENABLED', false),
            'url' => env('CDN_URL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue and Job Optimization
    |--------------------------------------------------------------------------
    */
    
    'queue' => [
        // Enable queue for heavy operations
        'enabled' => env('QUEUE_ENABLED', true),
        
        // Default queue connection
        'connection' => env('QUEUE_CONNECTION', 'database'),
        
        // Batch processing settings
        'batch' => [
            'enabled' => env('QUEUE_BATCH_ENABLED', true),
            'size' => env('QUEUE_BATCH_SIZE', 100),
        ],
        
        // Queue monitoring
        'monitoring' => env('QUEUE_MONITORING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring and Logging
    |--------------------------------------------------------------------------
    */
    
    'monitoring' => [
        // Enable performance monitoring
        'enabled' => env('PERFORMANCE_MONITORING', true),
        
        // Log slow operations
        'log_slow_operations' => env('LOG_SLOW_OPERATIONS', true),
        
        // Performance metrics collection
        'metrics' => env('COLLECT_METRICS', false),
        
        // Alert settings
        'alerts' => [
            'enabled' => env('PERFORMANCE_ALERTS', false),
            'slack_webhook' => env('SLACK_WEBHOOK_URL'),
            'email' => env('ADMIN_EMAIL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Optimization
    |--------------------------------------------------------------------------
    */
    
    'security' => [
        // Rate limiting
        'rate_limiting' => [
            'enabled' => env('RATE_LIMITING_ENABLED', true),
            'max_attempts' => env('RATE_LIMIT_ATTEMPTS', 60),
            'decay_minutes' => env('RATE_LIMIT_DECAY', 1),
        ],
        
        // CSRF optimization
        'csrf' => [
            'enabled' => env('CSRF_ENABLED', true),
            'except_routes' => [],
        ],
        
        // Input validation caching
        'validation_cache' => env('VALIDATION_CACHE_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Settings
    |--------------------------------------------------------------------------
    */
    
    'development' => [
        // Enable debug mode optimizations
        'debug_optimization' => env('DEBUG_OPTIMIZATION', false),
        
        // Query logging in development
        'query_logging' => env('DEV_QUERY_LOGGING', true),
        
        // Performance profiling
        'profiling' => env('DEV_PROFILING', false),
        
        // Mock heavy operations in development
        'mock_heavy_ops' => env('DEV_MOCK_HEAVY_OPS', false),
    ],

];
