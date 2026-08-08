<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for production environment
    |
    */

    /*
    |--------------------------------------------------------------------------
    | HTTPS Enforcement
    |--------------------------------------------------------------------------
    */
    'force_https' => env('FORCE_HTTPS', true),
    'hsts_enabled' => env('HSTS_ENABLED', true),
    'hsts_max_age' => env('HSTS_MAX_AGE', 31536000), // 1 year
    'hsts_include_subdomains' => true,
    'hsts_preload' => false,

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    */
    'session' => [
        'secure_cookie' => env('SESSION_SECURE_COOKIE', true),
        'http_only' => true,
        'same_site' => 'lax',
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Settings
    |--------------------------------------------------------------------------
    */
    'cors' => [
        'allowed_origins' => explode(',', env('ALLOWED_ORIGINS', 'https://your-domain.com')),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'exposed_headers' => [],
        'max_age' => 86400,
        'supports_credentials' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Security Policy
    |--------------------------------------------------------------------------
    */
    'csp' => [
        'enabled' => env('CSP_ENABLED', true),
        'default_src' => "'self'",
        'script_src' => "'self' 'unsafe-inline' 'unsafe-eval'",
        'style_src' => "'self' 'unsafe-inline'",
        'img_src' => "'self' data: https:",
        'font_src' => "'self' data:",
        'connect_src' => "'self'",
        'media_src' => "'self'",
        'object_src' => "'none'",
        'frame_src' => "'none'",
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        'enabled' => env('RATE_LIMIT_ENABLED', true),
        'attempts' => env('RATE_LIMIT_ATTEMPTS', 60),
        'decay_minutes' => env('RATE_LIMIT_DECAY_MINUTES', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Security
    |--------------------------------------------------------------------------
    */
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_special_chars' => true,
        'expire_days' => null, // Set to number for password expiration
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication
    |--------------------------------------------------------------------------
    */
    '2fa' => [
        'enabled' => env('2FA_ENABLED', false),
        'force_for_admins' => env('2FA_FORCE_FOR_ADMINS', false),
        'issuer' => env('APP_NAME', 'Z-Syst'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => env('AUDIT_LOGGING_ENABLED', true),
        'log_auth_attempts' => true,
        'log_data_changes' => true,
        'log_exports' => true,
        'log_imports' => true,
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 90),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Security
    |--------------------------------------------------------------------------
    */
    'api' => [
        'rate_limit' => env('API_RATE_LIMIT', 1000),
        'rate_limit_period' => env('API_RATE_LIMIT_PERIOD', 60), // minutes
        'require_api_key' => env('API_REQUIRE_KEY', true),
        'enable_ip_whitelist' => env('API_IP_WHITELIST_ENABLED', false),
        'ip_whitelist' => explode(',', env('API_IP_WHITELIST', '')),
    ],
];
