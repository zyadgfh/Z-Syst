<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// Throttles are on the POSTs only. The GETs render a form and cost nothing, so
// limiting them would mostly risk locking out a whole pharmacy behind one NAT
// address for no gain. The limiter definitions and the reasoning behind each one
// live in RouteServiceProvider::boot().
//
// POST /login has no throttle here on purpose: LoginRequest::authenticate()
// limits it to 5 attempts keyed on email|ip, hits on every failure and clears on
// success — tighter and better keyed than a route-level bucket.
Route::middleware('guest')->group(function () {

    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:auth-guest')
        ->name('register');

    Route::post('/otp-resend', [RegisteredUserController::class, 'otpResend'])
        ->middleware('throttle:auth-guest')
        ->name('otp-resend');
    Route::post('/otp-submit', [RegisteredUserController::class, 'otpSubmit'])
        ->middleware('throttle:auth-guest')
        ->name('otp-submit');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:auth-guest')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:auth-reset')
        ->name('password.store');

});

Route::middleware('auth')->group(function () {
    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
