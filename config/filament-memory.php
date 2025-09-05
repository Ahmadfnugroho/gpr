<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Memory Optimization Settings
    |--------------------------------------------------------------------------
    |
    | These settings control how Filament handles memory optimization
    | to prevent memory exhaustion errors.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Page Sizes
    |--------------------------------------------------------------------------
    |
    | Configure default pagination sizes based on your server's memory capacity.
    | The system will automatically adjust these based on available memory.
    |
    */

    'pagination' => [
        'default_page_size' => 25,
        'min_page_size' => 10,
        'max_page_size' => 100,
        'available_sizes' => [10, 25, 50, 100],
    ],

    /*
    |--------------------------------------------------------------------------
    | Memory Thresholds
    |--------------------------------------------------------------------------
    |
    | Define when to trigger memory optimization actions.
    |
    */

    'memory_thresholds' => [
        'warning_threshold' => 0.75,      // Show warning at 75% memory usage
        'optimization_threshold' => 0.70, // Start optimizations at 70%
        'critical_threshold' => 0.85,     // Critical level at 85%
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Optimization
    |--------------------------------------------------------------------------
    |
    | Settings for database query optimization.
    |
    */

    'query_optimization' => [
        'enable_eager_loading' => true,
        'enable_select_optimization' => true,
        'enable_query_caching' => env('FILAMENT_ENABLE_QUERY_CACHING', false),
        'default_chunk_size' => 50,
        'max_relations_depth' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching Settings
    |--------------------------------------------------------------------------
    |
    | Configure caching behavior for memory optimization.
    |
    */

    'caching' => [
        'enable_result_caching' => env('FILAMENT_ENABLE_RESULT_CACHING', false),
        'cache_duration' => 300, // 5 minutes
        'cache_prefix' => 'filament_memory_',
        'cache_driver' => env('FILAMENT_CACHE_DRIVER', 'file'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Export Settings
    |--------------------------------------------------------------------------
    |
    | Configure export limitations to prevent memory issues.
    |
    */

    'export' => [
        'max_export_records' => 1000,
        'chunk_export_size' => 100,
        'enable_streaming_export' => true,
        'export_timeout' => 300, // 5 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Actions Settings
    |--------------------------------------------------------------------------
    |
    | Configure bulk action limitations.
    |
    */

    'bulk_actions' => [
        'max_bulk_records' => 100,
        'chunk_bulk_size' => 25,
        'enable_bulk_confirmation' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Settings
    |--------------------------------------------------------------------------
    |
    | Enable debugging features for memory tracking.
    |
    */

    'debug' => [
        'enable_memory_monitoring' => env('FILAMENT_DEBUG_MEMORY', false),
        'log_memory_usage' => env('FILAMENT_LOG_MEMORY_USAGE', false),
        'show_memory_in_ui' => env('FILAMENT_SHOW_MEMORY_UI', false),
        'memory_log_channel' => 'filament_memory',
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Optimization
    |--------------------------------------------------------------------------
    |
    | Configure automatic memory optimization features.
    |
    */

    'auto_optimization' => [
        'enable_auto_garbage_collection' => true,
        'enable_auto_cache_clearing' => true,
        'enable_dynamic_pagination' => true,
        'enable_lazy_loading' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource Specific Settings
    |--------------------------------------------------------------------------
    |
    | Override settings for specific resources that might need different handling.
    |
    */

    'resource_overrides' => [
        // Example:
        // 'App\Filament\Resources\ProductResource' => [
        //     'pagination' => ['default_page_size' => 10],
        //     'export' => ['max_export_records' => 500],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configure performance monitoring and alerting.
    |
    */

    'monitoring' => [
        'enable_performance_tracking' => env('FILAMENT_PERFORMANCE_TRACKING', false),
        'slow_query_threshold' => 2.0, // seconds
        'memory_leak_detection' => env('FILAMENT_MEMORY_LEAK_DETECTION', false),
        'alert_on_memory_limit' => env('FILAMENT_ALERT_MEMORY_LIMIT', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Settings
    |--------------------------------------------------------------------------
    |
    | Fallback settings when memory optimization fails.
    |
    */

    'fallback' => [
        'emergency_page_size' => 5,
        'emergency_timeout' => 30,
        'disable_relations_on_memory_limit' => true,
        'fallback_to_simple_pagination' => true,
    ],

];
