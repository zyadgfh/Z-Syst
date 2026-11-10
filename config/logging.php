<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'slack'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'type' => 0, // 0: php://stderr, 4: SAPI
        ],

        // Sentry Integration
        'sentry' => [
            'driver' => 'sentry',
            'level' => 'error',
            'bubble' => true,
        ],

        // Performance Logging
        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => 'info',
            'days' => 7,
        ],

        // Security Logging
        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'warning',
            'days' => 30,
        ],

        // Application error log (App\Exceptions\RenderableException::report(),
        // App\Helpers\Logger, LogApiRequests middleware). These channels are
        // referenced by name at runtime; an undefined channel makes LogManager
        // throw from inside the exception handler and mask the original error.
        'errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/errors.log'),
            'level' => 'debug',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        // Critical-only log, kept separate so on-call tooling can tail it.
        'critical' => [
            'driver' => 'daily',
            'path' => storage_path('logs/critical.log'),
            'level' => 'critical',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        // Audit Logging
        'audit' => [
            'driver' => 'daily',
            'path' => storage_path('logs/audit.log'),
            'level' => 'info',
            'days' => 90,
        ],
    ],
];
