<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This value determines the default payment gateway to use for transactions.
    | Paymob is recommended for the Egyptian market.
    |
    */

    'default_gateway' => env('PAYMENT_GATEWAY', 'paymob'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for all supported payment gateways.
    |
    */

    'gateways' => [
        'paymob' => [
            'api_key' => env('PAYMOB_API_KEY'),
            'merchant_id' => env('PAYMOB_MERCHANT_ID'),
            'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
            'iframe_id' => env('PAYMOB_IFRAME_ID'),
            'environment' => env('PAYMOB_ENV', 'sandbox'), // sandbox | production

            'integration_ids' => [
                'vodafone_cash' => env('PAYMOB_INTEGRATION_VODAFONE'),
                'orange_cash' => env('PAYMOB_INTEGRATION_ORANGE'),
                'etisalat_cash' => env('PAYMOB_INTEGRATION_ETISALAT'),
                'we_pay' => env('PAYMOB_INTEGRATION_WE'),
                'instapay' => env('PAYMOB_INTEGRATION_INSTAPAY'),
                'card' => env('PAYMOB_INTEGRATION_CARD'),
            ],
            
            'webhook_url' => env('PAYMOB_WEBHOOK_URL'),
        ],

        'stripe' => [
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Limits
    |--------------------------------------------------------------------------
    |
    | Define minimum and maximum payment amounts
    |
    */

    'limits' => [
        'min_amount' => env('PAYMENT_MIN_AMOUNT', 1.00),
        'max_amount' => env('PAYMENT_MAX_AMOUNT', 50000.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for payment retries and webhook processing
    |
    */

    'retry' => [
        'max_attempts' => env('PAYMENT_MAX_RETRIES', 3),
        'backoff' => [30, 60, 300], // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for webhook handling
    |
    */

    'webhook' => [
        'queue' => 'payments-webhooks',
        'timeout' => env('PAYMENT_WEBHOOK_TIMEOUT', 30),
    ],
];