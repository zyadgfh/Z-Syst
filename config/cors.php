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

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // Never ship '*' to production: list explicit origins in CORS_ALLOWED_ORIGINS
    // (comma-separated). The default below matches the known deployment targets.
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'https://app.z-syst.com,http://localhost:3000'))
    ))),

    // Vercel preview deployments of the dashboard get in via pattern.
    'allowed_origins_patterns' => ['#^https://[a-z0-9-]+\.vercel\.app$#'],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    'supports_credentials' => true,

];
