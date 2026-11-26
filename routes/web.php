<?php

use App\Http\Controllers as Web;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Legacy dashboard alias — the SPA and older links still point at /dashboard.
Route::get('/dashboard', fn () => redirect()->route('admin.dashboard.index'))
    ->middleware('auth')
    ->name('dashboard.redirect');

// Manual Payment Routes (kept for supplier payments)
// Unauthenticated + unthrottled → free gateway-transaction spam (F-06).
Route::get('/payments-gateways/{plan_id}/{business_id}', [Web\PaymentController::class, 'index'])->middleware('throttle:20,1')->name('payments-gateways.index');
Route::post('/payments/{plan_id}/{gateway_id}', [Web\PaymentController::class, 'payment'])->middleware('throttle:20,1')->name('payments-gateways.payment');
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
    // Public and unauthenticated: it writes a store_orders row per call, so it
    // needs a per-IP budget or it is a free way to fill the table.
    Route::post('/checkout', [\App\Http\Controllers\Web\StorefrontController::class, 'checkout'])
        ->middleware('throttle:20,1')
        ->name('checkout');
});
// {gateway} is constrained at the route level so an unknown name never reaches
// the controller, and {transaction} must be numeric so the int type hint cannot
// be used to probe. 60/min leaves room for a gateway's own retry burst.
Route::get('/gateway/{gateway}/callback/{transaction}', \App\Http\Controllers\Web\GatewayWebhookController::class)
    ->where(['gateway' => 'paymob|fawry|instapay', 'transaction' => '[0-9]+'])
    ->middleware('throttle:60,1')
    ->name('web.gateway.callback');

// ═══ Stripe Connect Sample Integration (V2 Accounts, Storefront & Subscriptions) ═══
Route::prefix('connect')->name('stripe-connect.')->group(function () {
    // Merchant Connect Dashboard: create V2 accounts, onboard via Account Links,
    // view live status from Stripe API, create products, subscribe & open Billing Portal.
    Route::get('/', [\App\Http\Controllers\Web\StripeConnectController::class, 'index'])
        ->name('dashboard');
    Route::post('/accounts', [\App\Http\Controllers\Web\StripeConnectController::class, 'createAccount'])
        ->middleware('throttle:30,1')
        ->name('accounts.store');

    Route::where(['accountId' => 'acct_[A-Za-z0-9]+'])->group(function () {
        Route::post('/accounts/{accountId}/onboard', [\App\Http\Controllers\Web\StripeConnectController::class, 'onboard'])
            ->middleware('throttle:30,1')
            ->name('accounts.onboard');
        Route::get('/accounts/{accountId}/status', [\App\Http\Controllers\Web\StripeConnectController::class, 'accountStatus'])
            ->middleware('throttle:60,1')
            ->name('accounts.status');
        Route::post('/accounts/{accountId}/products', [\App\Http\Controllers\Web\StripeConnectController::class, 'createProduct'])
            ->middleware('throttle:30,1')
            ->name('products.store');
        Route::post('/accounts/{accountId}/subscribe', [\App\Http\Controllers\Web\StripeConnectController::class, 'subscribe'])
            ->middleware('throttle:30,1')
            ->name('subscriptions.checkout');
        Route::get('/accounts/{accountId}/subscription/success', [\App\Http\Controllers\Web\StripeConnectController::class, 'subscriptionSuccess'])
            ->name('subscription.success');
        Route::post('/accounts/{accountId}/billing-portal', [\App\Http\Controllers\Web\StripeConnectController::class, 'billingPortal'])
            ->middleware('throttle:30,1')
            ->name('billing-portal');

        // Public per-account Storefront & Direct Charge Checkout.
        // NOTE: Using the raw Stripe Account ID (`acct_...`) in the public URL is for
        // demo simplicity only. In production, route by a non-Stripe public identifier
        // (such as `businesses.store_slug` or an internal UUID) and look up the Stripe
        // account ID server-side.
        Route::get('/storefront/{accountId}', [\App\Http\Controllers\Web\StripeConnectStorefrontController::class, 'show'])
            ->name('storefront.show');
        Route::post('/storefront/{accountId}/checkout', [\App\Http\Controllers\Web\StripeConnectStorefrontController::class, 'checkout'])
            ->middleware('throttle:30,1')
            ->name('checkout.create');
        Route::get('/storefront/{accountId}/success', [\App\Http\Controllers\Web\StripeConnectStorefrontController::class, 'success'])
            ->name('checkout.success');
    });
});

// ═══ Stripe Webhooks (V2 Thin Events + V1 Snapshot Subscription Events) ═══
// Excluded from CSRF in App\Http\Middleware\VerifyCsrfToken because Stripe
// authenticates webhook requests via the `Stripe-Signature` HMAC header.
Route::prefix('webhooks/stripe')->name('webhooks.stripe.')->middleware('throttle:120,1')->group(function () {
    // V2 *Thin* Event Destination for Connected account requirement & capability updates.
    Route::post('/thin', [\App\Http\Controllers\Web\StripeWebhookController::class, 'handleThinEvents'])
        ->name('thin');
    // V1 *Snapshot* Webhook endpoint for platform subscriptions, invoices, customer & portal events.
    Route::post('/billing', [\App\Http\Controllers\Web\StripeWebhookController::class, 'handleBillingEvents'])
        ->name('billing');
});
