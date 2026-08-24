<?php

use App\Http\Controllers as Web;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\Webhook\ClerkWebhookController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Root route - redirect to login
Route::get('/', function () {
    return redirect('/login');
})->name('home');

// Payment Routes (Egyptian payment gateways + legacy manual)
Route::get('/payments-gateways/{plan_id}/{business_id}', [Web\PaymentController::class, 'index'])
    ->name('payments-gateways.index')
    ->middleware('throttle:60,1');
Route::post('/payments/{plan_id}/{gateway_id}', [Web\PaymentController::class, 'payment'])
    ->name('payments-gateways.payment')
    ->middleware('throttle:10,1');
Route::get('/payment/callback', [Web\PaymentController::class, 'paymentCallback'])
    ->name('payment.callback')
    ->middleware('throttle:100,1');
Route::get('/order-status', [Web\PaymentController::class, 'orderStatus'])
    ->name('order.status')
    ->middleware('throttle:60,1');

// Dark Mode Toggle
Route::post('/toggle-dark-mode', [Web\Admin\SettingController::class, 'toggleDarkMode'])->name('toggle-dark-mode');

// Clerk Webhook
Route::post('/webhooks/clerk', [ClerkWebhookController::class, 'handle'])
    ->name('webhooks.clerk');

// Payment Webhooks
Route::post('/webhooks/vodafone-cash', [PaymentWebhookController::class, 'vodafoneCash'])
    ->name('webhooks.vodafone-cash')
    ->middleware('throttle:100,1');
Route::post('/webhooks/bank-card', [PaymentWebhookController::class, 'bankCard'])
    ->name('webhooks.bank-card')
    ->middleware('throttle:100,1');
Route::post('/webhooks/fawry', [PaymentWebhookController::class, 'fawry'])
    ->name('webhooks.fawry')
    ->middleware('throttle:100,1');
Route::post('/webhooks/orange-cash', [PaymentWebhookController::class, 'orangeCash'])
    ->name('webhooks.orange-cash')
    ->middleware('throttle:100,1');
Route::post('/webhooks/instapay', [PaymentWebhookController::class, 'instaPay'])
    ->name('webhooks.instapay')
    ->middleware('throttle:100,1');
Route::get('/cache-clear', function () {
    if (!auth()->check() || !in_array(auth()->user()->role, ['admin', 'superadmin'])) {
        abort(403, 'Unauthorized');
    }

    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return back()->with('success', __('Cache has been cleared.'));
})->middleware('auth');

Route::get('/update', function () {
    if (!auth()->check() || !in_array(auth()->user()->role, ['superadmin'])) {
        abort(403, 'Unauthorized - Superadmin access only');
    }

    if (file_exists(base_path('storage/installed'))) {
        touch(base_path('vendor/autoload1.php'));
    }

    Artisan::call('module:publish Landing');
    Artisan::call('module:migrate Landing');
    Artisan::call('module:seed Landing');

    Artisan::call('migrate');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return redirect('/')->with('message', __('System updated successfully.'));
})->middleware('auth');

require __DIR__.'/auth.php';
require __DIR__.'/customer.php';
