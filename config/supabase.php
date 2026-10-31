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
    'jwt_secret' => env('SUPABASE_JWT_SECRET'),

    'auth' => [
        // GoTrue endpoints used instead of storing passwords in Postgres.
        'token_path' => '/auth/v1/token',
        'user_path' => '/auth/v1/user',
        'logout_path' => '/auth/v1/logout',
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
        'admin' => ['*'],
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
