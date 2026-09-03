<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Clerk Publishable Key
    |--------------------------------------------------------------------------
    |
    | The publishable key from your Clerk application settings.
    | This is used for client-side initialization.
    |
    */
    'publishable_key' => env('VITE_CLERK_PUBLISHABLE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Clerk Secret Key
    |--------------------------------------------------------------------------
    |
    | The secret key from your Clerk application settings.
    | NEVER expose this in client-side code.
    |
    */
    'secret_key' => env('CLERK_SECRET_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Clerk Frontend API URL
    |--------------------------------------------------------------------------
    |
    | Auto-derived from the publishable key if not set.
    |
    */
    'frontend_api' => env('CLERK_FRONTEND_API'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Signing Secret
    |--------------------------------------------------------------------------
    |
    | The webhook signing secret used to verify incoming webhook payloads.
    | Set this in your Clerk Dashboard under Webhooks.
    |
    */
    'webhook_signing_secret' => env('CLERK_WEBHOOK_SIGNING_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Authorized Parties
    |--------------------------------------------------------------------------
    |
    | List of authorized parties for token verification.
    | Typically your app's domain(s).
    |
    */
    'authorized_parties' => [
        env('APP_URL', 'http://localhost:8000'),
    ],
];
