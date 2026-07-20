<?php

use Illuminate\Support\Facades\Route;

// Auth Module Routes
Route::middleware('api')->prefix('v1/auth')->name('auth.')->group(function () {
    // Public routes (no auth required)
    Route::post('register', [\App\Modules\Auth\Infrastructure\Controllers\AuthController::class, 'register'])
        ->name('register');

    Route::post('login', [\App\Modules\Auth\Infrastructure\Controllers\AuthController::class, 'login'])
        ->name('login');

    Route::post('forgot-password', [\App\Modules\Auth\Infrastructure\Controllers\PasswordResetController::class, 'forgotPassword'])
        ->name('forgot-password');

    Route::post('reset-password', [\App\Modules\Auth\Infrastructure\Controllers\PasswordResetController::class, 'resetPassword'])
        ->name('reset-password');

    // Protected routes (auth required)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [\App\Modules\Auth\Infrastructure\Controllers\AuthController::class, 'logout'])
            ->name('logout');

        Route::get('profile', [\App\Modules\Auth\Infrastructure\Controllers\AuthController::class, 'profile'])
            ->name('profile');

        Route::put('profile', [\App\Modules\Auth\Infrastructure\Controllers\AuthController::class, 'updateProfile'])
            ->name('update-profile');

        Route::post('change-password', [\App\Modules\Auth\Infrastructure\Controllers\PasswordResetController::class, 'changePassword'])
            ->name('change-password');
    });
});
