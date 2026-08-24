<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp API
    |--------------------------------------------------------------------------
    */
    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v18.0'),
        'api_key' => env('WHATSAPP_API_KEY'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'enabled' => (bool) env('WHATSAPP_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Secrets
    |--------------------------------------------------------------------------
    */
    'payment' => [
        'vodafone_cash_api_secret' => env('VODAFONE_CASH_API_SECRET'),
        'bank_card_api_secret' => env('BANK_CARD_API_SECRET'),
        'fawry_security_key' => env('FAWRY_SECURITY_KEY'),
        'orange_cash_api_secret' => env('ORANGE_CASH_API_SECRET'),
        'instapay_api_secret' => env('INSTAPAY_API_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Firebase Cloud Messaging
    |--------------------------------------------------------------------------
    */
    'firebase' => [
        'project_id'   => env('VITE_FIREBASE_PROJECT_ID', 'z-syst'),
        'server_key'   => env('FIREBASE_SERVER_KEY'),
        'credentials'  => env('FIREBASE_SERVICE_ACCOUNT_PATH'),
    ],
];
