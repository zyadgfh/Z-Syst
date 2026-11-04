<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Never ship '*' to production: list explicit origins in CORS_ALLOWED_ORIGINS
    // (comma-separated). The default below matches the known deployment targets.
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'https://app.z-syst.com,http://localhost:3000'))
    ))),

    // Keep this empty in production. Add a narrowly-scoped pattern only when
    // preview deployments are explicitly needed.
    'allowed_origins_patterns' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS_PATTERNS', ''))
    ))),

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'Origin', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 3600,

    // Supabase JWTs are sent in Authorization; cookies are not required.
    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),

];
