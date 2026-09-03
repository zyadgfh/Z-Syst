<?php

use App\Http\Controllers\Api;
use App\Http\Controllers\PosPaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // POS Payments
    Route::prefix('payments')->group(function () {
        Route::get('/gateways', [PosPaymentController::class, 'getAvailableGateways']);
        Route::post('/process', [PosPaymentController::class, 'processPayment']);
        Route::post('/verify', [PosPaymentController::class, 'verifyPayment']);
        Route::post('/refund', [PosPaymentController::class, 'processRefund']);
        Route::post('/calculate-change', [PosPaymentController::class, 'calculateChange']);
        Route::get('/stats', [PosPaymentController::class, 'getPaymentStats']);
    });
});
