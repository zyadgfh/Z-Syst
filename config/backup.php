<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for database and file backups
    |
    */

    'enabled' => env('BACKUP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Backup Storage
    |--------------------------------------------------------------------------
    */
    'disk' => env('BACKUP_DISK', 's3'),
    'path' => env('BACKUP_PATH', 'backups'),

    /*
    |--------------------------------------------------------------------------
    | Backup Schedule
    |--------------------------------------------------------------------------
    */
    'schedule' => env('BACKUP_SCHEDULE', '0 2 * * *'), // Daily at 2 AM

    /*
    |--------------------------------------------------------------------------
    | Retention Policy
    |--------------------------------------------------------------------------
    */
    'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
    'max_backups' => env('BACKUP_MAX_BACKUPS', 10),

    /*
    |--------------------------------------------------------------------------
    | Backup Components
    |--------------------------------------------------------------------------
    */
    'components' => [
        'database' => true,
        'files' => true,
        'assets' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Backup Settings
    |--------------------------------------------------------------------------
    */
    'database' => [
        'compress' => true,
        'dump_command' => 'mysqldump',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Backup Settings
    |--------------------------------------------------------------------------
    */
    'files' => [
        'include' => [
            'storage/app',
            'storage/logs',
        ],
        'exclude' => [
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'node_modules',
            '.git',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'on_success' => env('BACKUP_NOTIFY_ON_SUCCESS', true),
        'on_failure' => env('BACKUP_NOTIFY_ON_FAILURE', true),
        'email' => env('BACKUP_NOTIFY_EMAIL', null),
    ],
];
