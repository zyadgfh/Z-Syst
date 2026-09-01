<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::middleware(['business.context', 'throttle:20,1'])->group(function () {
    // Subscriptions
    Route::apiResource('subscriptions', Api\SubscriptionController::class)->except('show');
    Route::get('subscriptions/{subscription}', [Api\SubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::post('subscriptions/{subscription}/upgrade', [Api\SubscriptionController::class, 'upgrade'])->name('subscriptions.upgrade');
    Route::post('subscriptions/{subscription}/downgrade', [Api\SubscriptionController::class, 'downgrade'])->name('subscriptions.downgrade');
    Route::post('subscriptions/{subscription}/cancel', [Api\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('subscriptions/{subscription}/renew', [Api\SubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::post('subscriptions/{subscription}/generate-invoice', [Api\SubscriptionController::class, 'generateInvoice'])->name('subscriptions.generate-invoice');
    Route::get('subscriptions/usage', [Api\SubscriptionController::class, 'usage'])->name('subscriptions.usage');
    Route::get('subscriptions/check-limits', [Api\SubscriptionController::class, 'checkLimits'])->name('subscriptions.check-limits');

    // Supabase Auth
    Route::prefix('supabase')->group(function () {
        Route::post('/logout', [Api\SupabaseAuthController::class, 'logout']);
        Route::post('/refresh', [Api\SupabaseAuthController::class, 'refresh']);
        Route::get('/me', [Api\SupabaseAuthController::class, 'me']);
        Route::post('/forgot-password', [Api\SupabaseAuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [Api\SupabaseAuthController::class, 'resetPassword']);
    });

    // Supabase Storage
    Route::prefix('supabase/storage')->middleware('throttle.payment')->group(function () {
        Route::post('/upload', [Api\SupabaseStorageController::class, 'upload']);
        Route::post('/upload-multiple', [Api\SupabaseStorageController::class, 'uploadMultiple']);
        Route::post('/delete', [Api\SupabaseStorageController::class, 'delete']);
        Route::get('/list', [Api\SupabaseStorageController::class, 'listFiles']);
        Route::get('/download', [Api\SupabaseStorageController::class, 'download']);
        Route::get('/signed-url', [Api\SupabaseStorageController::class, 'createSignedUrl']);
    });
});
