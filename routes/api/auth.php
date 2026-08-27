<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:auth')->group(function () {
    Route::post('/sign-in', [Api\Auth\AuthController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::post('/submit-otp', [Api\Auth\AuthController::class, 'submitOtp'])
        ->middleware('throttle:10,1');
    Route::post('/sign-up', [Api\Auth\AuthController::class, 'signUp'])
        ->middleware('throttle:3,1');
    Route::post('/resend-otp', [Api\Auth\AuthController::class, 'resendOtp'])
        ->middleware('throttle:3,1');

    Route::post('/supabase/register', [Api\SupabaseAuthController::class, 'register'])
        ->middleware('throttle:3,1');
    Route::post('/supabase/login', [Api\SupabaseAuthController::class, 'login'])
        ->middleware('throttle:5,1');
});

Route::middleware('throttle:5,5')->group(function () {
    Route::post('/send-reset-code', [Api\Auth\ZSystForgotPasswordController::class, 'sendResetCode']);
    Route::post('/verify-reset-code', [Api\Auth\ZSystForgotPasswordController::class, 'verifyResetCode']);
    Route::post('/password-reset', [Api\Auth\ZSystForgotPasswordController::class, 'resetPassword']);
});

// 2FA Routes (authenticated, with rate limiting)
Route::middleware(['auth:sanctum', 'throttle:10,1'])->prefix('2fa')->group(function () {
    Route::get('/status', [Api\TwoFactorAuthController::class, 'status']);
    Route::post('/setup', [Api\TwoFactorAuthController::class, 'setup']);
    Route::post('/confirm', [Api\TwoFactorAuthController::class, 'confirm']);
    Route::post('/verify', [Api\TwoFactorAuthController::class, 'verify']);
    Route::post('/disable', [Api\TwoFactorAuthController::class, 'disable']);
    Route::post('/recovery-codes', [Api\TwoFactorAuthController::class, 'regenerateRecoveryCodes']);
});
