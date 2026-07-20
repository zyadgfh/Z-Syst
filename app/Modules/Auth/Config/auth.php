<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Authentication Module Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for the Auth module configuration.
    |
    */

    'enable_2fa' => env('AUTH_ENABLE_2FA', true),
    'enable_otp' => env('AUTH_ENABLE_OTP', true),
    'password_reset_timeout' => env('PASSWORD_RESET_TIMEOUT', 60),
];
