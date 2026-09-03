<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This value should be the domain where the Horizon dashboard will be
    | accessible. This value will also prefix the routes registered by
    | Horizon. Typically this will be the root of your application domain.
    |
    */

    'domain' => env('HORIZON_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This value sets the path where Horizon's dashboard will be accessible
    | from. By default, this is "/horizon".
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This value determines which Redis connection your Horizon instances
    | should use. This connection must exist in your Redis configuration.
    |
    */

    'redis' => env('REDIS_CONNECTION', 'redis'),

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. The Supervisor configuration options and the
    | number of workers configured here will be used by the Horizon
    | Supervisor when it starts and manages your worker processes.
    |
    */

    'environments' => [

        'production' => [
            'supervisor-1' => [
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
                'queue' => ['default', 'notifications', 'emails', 'sms'],
                'try' => 3,
                'timeout' => 60,
                'nice' => 0,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
                'queue' => ['default', 'notifications'],
                'try' => 3,
                'timeout' => 60,
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job TTL
    |--------------------------------------------------------------------------
    |
    | The TTL (Time To Live) in seconds for jobs that have been dispatched
    | but not processed. This prevents stuck jobs from accumulating.
    |
    */

    'job_timeout' => 60,

    /*
    |--------------------------------------------------------------------------
    | Job Retry Maximum
    |--------------------------------------------------------------------------
    |
    | The maximum number of times a job may be attempted before being marked
    | as failed permanently.
    |
    */

    'max_jobs' => 1000,

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the behavior of Horizon when
    | your application enters maintenance mode. By default, Horizon will
    | continue to process jobs, but with a reduced number of workers.
    |
    */

    'backoff' => [
        'production' => 30,
        'local' => 5,
    ],

];
