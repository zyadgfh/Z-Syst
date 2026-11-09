<?php

use App\Http\Controllers as Web;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Legacy dashboard alias — the SPA and older links still point at /dashboard.
Route::get('/dashboard', fn () => redirect()->route('admin.dashboard.index'))
    ->middleware('auth')
    ->name('dashboard.redirect');

// Manual Payment Routes (kept for supplier payments)
Route::get('/payments-gateways/{plan_id}/{business_id}', [Web\PaymentController::class, 'index'])->name('payments-gateways.index');
Route::post('/payments/{plan_id}/{gateway_id}', [Web\PaymentController::class, 'payment'])->name('payments-gateways.payment');
Route::get('/order-status', [Web\PaymentController::class, 'orderStatus'])->name('order.status');

// Egyptian Payment Gateway Routes
Route::group([
    'namespace' => 'App\Library',
], function () {
    // Vodafone Cash
    Route::get('/vodafone-cash', 'VodafoneCash@view')->name('vodafone-cash.view');
    Route::post('/vodafone-cash/status', 'VodafoneCash@status')->name('vodafone-cash.status');
    
    // Orange Cash
    Route::get('/orange-cash', 'OrangeCash@view')->name('orange-cash.view');
    Route::post('/orange-cash/status', 'OrangeCash@status')->name('orange-cash.status');
    
    // InstaPay
    Route::get('/instapay', 'InstaPay@view')->name('instapay.view');
    Route::post('/instapay/status', 'InstaPay@status')->name('instapay.status');
    
    // Bank Card
    Route::get('/bank-card', 'BankCard@view')->name('bank-card.view');
    Route::post('/bank-card/status', 'BankCard@status')->name('bank-card.status');
    
    // Fawry
    Route::get('/fawry', 'Fawry@view')->name('fawry.view');
    Route::post('/fawry/status', 'Fawry@status')->name('fawry.status');
    
    // Cash Payment
    Route::get('/cash-payment', 'CashPayment@view')->name('cash-payment.view');
    Route::post('/cash-payment/status', 'CashPayment@status')->name('cash-payment.status');
});

// Payment success/failed routes
Route::get('/payment/success', [Web\PaymentController::class, 'success'])->name('payment.success')->middleware(['auth', 'throttle:30,1']);
Route::get('/payment/failed', [Web\PaymentController::class, 'failed'])->name('payment.failed')->middleware('throttle:30,1');

require __DIR__.'/auth.php';

// ═══ Storefront (per-tenant public shop) + payment gateway callbacks ═══
Route::prefix('store/{slug}')->name('store.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Web\StorefrontController::class, 'show'])->name('show');
    Route::post('/checkout', [\App\Http\Controllers\Web\StorefrontController::class, 'checkout'])->name('checkout');
});
Route::get('/gateway/{gateway}/callback/{transaction}', \App\Http\Controllers\Web\GatewayWebhookController::class)->name('web.gateway.callback');
