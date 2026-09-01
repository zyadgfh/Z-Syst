<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "log", "twilio", "infobip", "custom"
    |
    | - log:     Logs SMS to Laravel log (default, for development)
    | - twilio:  Send via Twilio API
    | - infobip: Send via Infobip API
    | - custom:  Send via custom HTTP API
    |
    */

    'driver' => env('SMS_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Twilio Configuration
    |--------------------------------------------------------------------------
    */

    'account_sid' => env('TWILIO_ACCOUNT_SID'),
    'auth_token' => env('TWILIO_AUTH_TOKEN'),
    'from_number' => env('TWILIO_FROM_NUMBER'),

    /*
    |--------------------------------------------------------------------------
    | Infobip Configuration
    |--------------------------------------------------------------------------
    */

    'api_url' => env('SMS_API_URL', 'https://api.infobip.com/sms/2/text/advanced'),
    'api_key' => env('SMS_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | General
    |--------------------------------------------------------------------------
    */

    'enabled' => env('SMS_ENABLED', false),
];
