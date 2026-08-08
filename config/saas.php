<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SaaS Configuration
    |--------------------------------------------------------------------------
    |
    | SaaS specific configuration for multi-tenant pharmacy management system
    |
    */

    'enabled' => env('SAAS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Tenant Limits
    |--------------------------------------------------------------------------
    */
    'max_tenants' => env('SAAS_MAX_TENANTS', 1000),
    'max_users_per_tenant' => env('SAAS_MAX_USERS_PER_TENANT', 50),
    'max_products_per_tenant' => env('SAAS_MAX_PRODUCTS_PER_TENANT', 10000),

    /*
    |--------------------------------------------------------------------------
    | Subscription Defaults
    |--------------------------------------------------------------------------
    */
    'default_plan_id' => env('SAAS_DEFAULT_PLAN_ID', 1),
    'trial_days' => env('SAAS_TRIAL_DAYS', 14),
    'grace_period_days' => env('SAAS_GRACE_PERIOD_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Tenant Isolation
    |--------------------------------------------------------------------------
    */
    'isolation_mode' => env('SAAS_ISOLATION_MODE', 'shared'), // shared, dedicated, hybrid

    /*
    |--------------------------------------------------------------------------
    | Registration
    |--------------------------------------------------------------------------
    */
    'registration_enabled' => env('SAAS_REGISTRATION_ENABLED', true),
    'require_email_verification' => env('SAAS_REQUIRE_EMAIL_VERIFICATION', true),
    'auto_activate_trial' => env('SAAS_AUTO_ACTIVATE_TRIAL', true),

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
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'multi_warehouse' => env('SAAS_FEATURE_MULTI_WAREHOUSE', true),
        'insurance' => env('SAAS_FEATURE_INSURANCE', true),
        'loyalty_crm' => env('SAAS_FEATURE_LOYALTY_CRM', true),
        'drug_traceability' => env('SAAS_FEATURE_DRUG_TRACEABILITY', true),
        'receipt_printing' => env('SAAS_FEATURE_RECEIPT_PRINTING', true),
        'api_access' => env('SAAS_FEATURE_API_ACCESS', false),
    ],
];
