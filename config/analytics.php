<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Analytics Cache Settings
    |--------------------------------------------------------------------------
    |
    | These settings control how long analytics data is cached to improve
    | performance and reduce database load.
    |
    */

    'cache' => [
        'kpis_ttl' => env('ANALYTICS_KPIS_CACHE_TTL', 300), // 5 minutes
        'sales_trends_ttl' => env('ANALYTICS_SALES_TRENDS_CACHE_TTL', 600), // 10 minutes
        'inventory_summary_ttl' => env('ANALYTICS_INVENTORY_SUMMARY_CACHE_TTL', 300), // 5 minutes
        'low_stock_ttl' => env('ANALYTICS_LOW_STOCK_CACHE_TTL', 300), // 5 minutes
        'expiring_products_ttl' => env('ANALYTICS_EXPIRING_PRODUCTS_CACHE_TTL', 300), // 5 minutes
        'dead_stock_ttl' => env('ANALYTICS_DEAD_STOCK_CACHE_TTL', 1800), // 30 minutes
        'fast_moving_ttl' => env('ANALYTICS_FAST_MOVING_CACHE_TTL', 600), // 10 minutes
        'stock_turnover_ttl' => env('ANALYTICS_STOCK_TURNOVER_CACHE_TTL', 1800), // 30 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configure when and how stock notifications are sent.
    |
    */

    'notifications' => [
        'low_stock' => [
            'enabled' => env('LOW_STOCK_NOTIFICATIONS_ENABLED', true),
            'channels' => env('LOW_STOCK_NOTIFICATION_CHANNELS', 'mail,database'),
            'threshold_percentage' => env('LOW_STOCK_THRESHOLD_PERCENTAGE', 100), // Alert when at or below reorder level
        ],
        'expiry' => [
            'enabled' => env('EXPIRY_NOTIFICATIONS_ENABLED', true),
            'channels' => env('EXPIRY_NOTIFICATION_CHANNELS', 'mail,database'),
            'warning_days' => env('EXPIRY_WARNING_DAYS', 30), // Warn 30 days before expiry
            'urgent_days' => env('EXPIRY_URGENT_DAYS', 7), // Mark as urgent 7 days before expiry
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Export Settings
    |--------------------------------------------------------------------------
    |
    | Settings for generating and storing export reports.
    |
    */

    'exports' => [
        'storage_disk' => env('ANALYTICS_EXPORT_DISK', 'local'),
        'storage_path' => env('ANALYTICS_EXPORT_PATH', 'exports'),
        'cleanup_days' => env('ANALYTICS_EXPORT_CLEANUP_DAYS', 7), // Delete exports older than 7 days
        'max_rows_per_export' => env('ANALYTICS_MAX_EXPORT_ROWS', 10000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Limits
    |--------------------------------------------------------------------------
    |
    | Maximum limits for dashboard queries to prevent performance issues.
    |
    */

    'limits' => [
        'max_top_products' => env('ANALYTICS_MAX_TOP_PRODUCTS', 50),
        'max_low_stock_products' => env('ANALYTICS_MAX_LOW_STOCK', 50),
        'max_expiring_products' => env('ANALYTICS_MAX_EXPIRING', 50),
        'max_dead_stock_products' => env('ANALYTICS_MAX_DEAD_STOCK', 50),
        'max_fast_moving_products' => env('ANALYTICS_MAX_FAST_MOVING', 50),
        'max_export_rows' => env('ANALYTICS_MAX_EXPORT_ROWS', 10000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Turnover Categories
    |--------------------------------------------------------------------------
    |
    | Define the turnover rate categories for inventory analysis.
    | Turnover rate = (sold / average stock) * (365 / days)
    |
    */

    'turnover_categories' => [
        'fast' => [
            'min_rate' => 12,
            'label' => 'Fast Moving',
            'color' => '#10B981', // green
        ],
        'moderate' => [
            'min_rate' => 6,
            'max_rate' => 12,
            'label' => 'Moderate Moving',
            'color' => '#F59E0B', // amber
        ],
        'slow' => [
            'min_rate' => 3,
            'max_rate' => 6,
            'label' => 'Slow Moving',
            'color' => '#EF4444', // red
        ],
        'dead' => [
            'max_rate' => 3,
            'label' => 'Dead Stock',
            'color' => '#6B7280', // gray
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reorder Urgency Levels
    |--------------------------------------------------------------------------
    |
    | Define urgency levels for low stock products.
    |
    */

    'reorder_urgency' => [
        'critical' => [
            'condition' => 'quantity == 0',
            'label' => 'Critical',
            'color' => '#DC2626', // red
        ],
        'high' => [
            'condition' => 'quantity <= reorder_level * 0.5',
            'label' => 'High',
            'color' => '#EF4444', // red
        ],
        'medium' => [
            'condition' => 'quantity <= reorder_level',
            'label' => 'Medium',
            'color' => '#F59E0B', // amber
        ],
        'low' => [
            'condition' => 'quantity > reorder_level',
            'label' => 'Low',
            'color' => '#10B981', // green
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scheduled Task Settings
    |--------------------------------------------------------------------------
    |
    | Configure the daily analytics calculation task.
    |
    */

    'scheduled_tasks' => [
        'daily_metrics' => [
            'enabled' => env('ANALYTICS_DAILY_METRICS_ENABLED', true),
            'schedule' => env('ANALYTICS_DAILY_METRICS_SCHEDULE', '0:00'), // Runs at midnight
        ],
        'export_cleanup' => [
            'enabled' => env('ANALYTICS_EXPORT_CLEANUP_ENABLED', true),
            'schedule' => env('ANALYTICS_EXPORT_CLEANUP_SCHEDULE', '1:00'), // Runs at 1 AM
        ],
    ],
];