<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Supabase project (Path B: Laravel as a façade over Supabase Postgres)
    |--------------------------------------------------------------------------
    | Project ref: atvttthrqsybjjwrbiyl  (org crwijxhkoetuectaaqke, eu-central-1)
    | The database is Postgres 17 with RLS on every tenant table; identity lives
    | in auth.users and is mapped to public.app_users.
    */

    'url' => env('SUPABASE_URL', 'https://atvttthrqsybjjwrbiyl.supabase.co'),
    'anon_key' => env('SUPABASE_ANON_KEY'),
    'service_role_key' => env('SUPABASE_SERVICE_ROLE_KEY'),

    // Local dev / CI fallback: verify credentials against the local users
    // table (bcrypt) instead of GoTrue, so the app runs without Supabase
    // credentials. Production must keep this disabled.
    'fallback_eloquent' => env('SUPABASE_FALLBACK_ELOQUENT', false),
    'jwt_secret' => env('SUPABASE_JWT_SECRET'),

    // Access-token verification for Laravel API requests. The JWT secret is
    // the Supabase legacy HS256 signing secret, never the service-role key.
    'jwt' => [
        'secret' => env('SUPABASE_JWT_SECRET'),
        'issuer' => env('SUPABASE_JWT_ISSUER', rtrim(env('SUPABASE_URL', ''), '/').'/auth/v1'),
        'audience' => env('SUPABASE_JWT_AUDIENCE', 'authenticated'),
        'leeway' => (int) env('SUPABASE_JWT_LEEWAY', 10),
    ],

    'auth' => [
        // GoTrue endpoints used instead of storing passwords in Postgres.
        'token_path' => '/auth/v1/token',
        'user_path' => '/auth/v1/user',
        'logout_path' => '/auth/v1/logout',
        'recover_path' => '/auth/v1/recover',
        'timeout' => (int) env('SUPABASE_HTTP_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | CAPTCHA (hCaptcha) — must stay in lock-step with the Supabase project
    |--------------------------------------------------------------------------
    | Once "Enable CAPTCHA protection" is turned on in the Supabase dashboard,
    | GoTrue rejects every password grant that does not carry a valid
    | captcha_token. If this app does not render the widget and forward the
    | token, EVERY login fails. Therefore: enable the client side here first,
    | verify it works, and only then switch the project setting on.
    |
    | The hCaptcha SECRET key is stored only in Supabase — it is what GoTrue
    | uses to verify tokens. It must NOT be copied into this app unless you
    | need local verification; the browser only ever needs the public site key.
    */

    'captcha' => [
        'enabled' => (bool) env('SUPABASE_CAPTCHA_ENABLED', false),
        'provider' => env('SUPABASE_CAPTCHA_PROVIDER', 'hcaptcha'),
        // Public key rendered into the login form.
        'site_key' => env('HCAPTCHA_SITE_KEY'),
        // Where GoTrue looks for the token in the password-grant body.
        'token_field' => 'captcha_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Row Level Security enforcement
    |--------------------------------------------------------------------------
    | The app connects as the `postgres` role, which bypasses RLS. Every web
    | request is therefore wrapped in a transaction that does
    |   SET LOCAL ROLE authenticated (or anon)
    | and publishes the JWT claims so private.current_business_id() resolves.
    | Never disable this in production.
    */

    'enforce_rls' => (bool) env('SUPABASE_ENFORCE_RLS', true),

    /*
    |--------------------------------------------------------------------------
    | RBAC — Supabase keeps the role on app_users.role (text), there is no
    | Spatie table set. These abilities are resolved by a Gate::before hook.
    | '*' means every ability.
    |--------------------------------------------------------------------------
    */

    'roles' => ['superadmin', 'admin', 'manager', 'pharmacist', 'cashier', 'accountant', 'viewer'],

    'permissions' => [
        'superadmin' => ['*'],

        /*
         * Admin = full business CRUD, minus platform-level management
         * (roles.*, permissions.*, plans.*, business.*, currencies.*),
         * destructive audit tools (audit-logs-delete) and user deletion.
         * The vocabulary MUST mirror the `permission:<resource>-<action>`
         * middleware declared in app/Http/Controllers/Admin/*.php, and
         * stay in sync with database/seeders/PermissionSeeder.php.
         */
        'admin' => [
            // day-to-day visibility
            'dashboard-read', 'reports-read', 'notifications-read',
            'settings-read', 'settings-update', 'audit-logs-read',

            // team & locations
            'users-read', 'users-create', 'users-update',
            'warehouses-create', 'warehouses-read', 'warehouses-update', 'warehouses-delete',

            // operations
            'stock-transfers-create', 'stock-transfers-read', 'stock-transfers-update', 'stock-transfers-delete',
            'traceability-create', 'traceability-read', 'traceability-update', 'traceability-delete',
            'prescriptions-create', 'prescriptions-read', 'prescriptions-update', 'prescriptions-delete',
            'receipts-create', 'receipts-read', 'receipts-update', 'receipts-delete',

            // finance & insurance
            'insurance-companies-create', 'insurance-companies-read', 'insurance-companies-update', 'insurance-companies-delete',
            'insurance-policies-create', 'insurance-policies-read', 'insurance-policies-update', 'insurance-policies-delete',
            'insurance-claims-create', 'insurance-claims-read', 'insurance-claims-update', 'insurance-claims-delete',
            'loyalty-create', 'loyalty-read', 'loyalty-update', 'loyalty-delete',
            'banners-create', 'banners-read', 'banners-update', 'banners-delete',
        ],

        // Staff (app_users.role = 'staff', API/POS): read-only + file claims.
        'staff' => [
            'dashboard-read', 'reports-read', 'notifications-read',
            'warehouses-read', 'stock-transfers-read', 'traceability-read',
            'prescriptions-read', 'prescriptions-create',
            'receipts-read', 'loyalty-read',
            'insurance-claims-read', 'insurance-claims-create',
        ],

        // Legacy alias kept for old records — same abilities as staff.
        'shop-owner' => [
            'dashboard-read', 'reports-read', 'notifications-read',
            'warehouses-read', 'stock-transfers-read', 'traceability-read',
            'prescriptions-read', 'prescriptions-create',
            'receipts-read', 'loyalty-read',
            'insurance-claims-read', 'insurance-claims-create',
        ],

        'manager' => [
            'products.view', 'products.create', 'products.update', 'products.delete',
            'inventory.view', 'inventory.adjust', 'purchases.view', 'purchases.create',
            'sales.view', 'sales.create', 'sales.refund', 'reports.view',
            'users.view', 'branches.view', 'settings.update',
        ],
        'pharmacist' => [
            'products.view', 'inventory.view', 'sales.view', 'sales.create',
            'prescriptions.view', 'prescriptions.create', 'reports.view',
        ],
        'cashier' => ['products.view', 'inventory.view', 'sales.view', 'sales.create'],
        'accountant' => [
            'sales.view', 'purchases.view', 'expenses.view', 'expenses.create',
            'incomes.view', 'incomes.create', 'reports.view', 'reports.financial',
        ],
        'viewer' => ['products.view', 'inventory.view', 'sales.view', 'reports.view'],
    ],
];
