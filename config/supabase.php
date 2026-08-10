<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supabase URL
    |--------------------------------------------------------------------------
    |
    | The URL of your Supabase project. This can be found in your Supabase
    | dashboard under Project Settings > API.
    |
    */
    'url' => env('SUPABASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Supabase Key
    |--------------------------------------------------------------------------
    |
    | The anon/public key for your Supabase project. This key can be used
    | for client-side operations and has limited permissions.
    |
    */
    'key' => env('SUPABASE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Supabase Service Role Key
    |--------------------------------------------------------------------------
    |
    | The service role key for admin operations. This key has full access
    | to your Supabase project and should be kept secret.
    |
    */
    'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Database Connection
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to the Supabase PostgreSQL database.
    |
    */
    'database' => [
        'host' => env('SUPABASE_DB_HOST'),
        'port' => env('SUPABASE_DB_PORT', 5432),
        'database' => env('SUPABASE_DB_DATABASE'),
        'username' => env('SUPABASE_DB_USERNAME', 'postgres'),
        'password' => env('SUPABASE_DB_PASSWORD'),
        'schema' => 'public',
        'sslmode' => 'require',
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Supabase Storage integration.
    |
    */
    'storage' => [
        'enabled' => true,
        'bucket' => env('SUPABASE_STORAGE_BUCKET', 'z-syst-uploads'),
        'cdn_url' => env('SUPABASE_STORAGE_CDN_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Real-time Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Supabase Real-time subscriptions.
    |
    */
    'realtime' => [
        'enabled' => true,
        'channels' => [
            'sales' => true,
            'inventory' => true,
            'notifications' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Supabase Auth integration.
    |
    */
    'auth' => [
        'enabled' => true,
        'jwt_secret' => env('SUPABASE_JWT_SECRET'),
        'auto_refresh' => true,
        'storage_key' => 'supabase_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Edge Functions Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Supabase Edge Functions.
    |
    */
    'functions' => [
        'enabled' => false,
        'url' => env('SUPABASE_FUNCTIONS_URL'),
    ],
];