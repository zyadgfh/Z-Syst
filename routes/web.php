<?php

use App\Http\Controllers as Web;
use App\Http\Controllers\PaymentWebhookController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

// Payment Routes (Egyptian payment gateways + legacy manual)
Route::get('/payments-gateways/{plan_id}/{business_id}', [Web\PaymentController::class, 'index'])->name('payments-gateways.index');
Route::post('/payments/{plan_id}/{gateway_id}', [Web\PaymentController::class, 'payment'])->name('payments-gateways.payment');
Route::get('/payment/callback', [Web\PaymentController::class, 'paymentCallback'])->name('payment.callback');
Route::get('/order-status', [Web\PaymentController::class, 'orderStatus'])->name('order.status');

// Payment Webhooks
Route::post('/webhooks/vodafone-cash', [PaymentWebhookController::class, 'vodafoneCash'])->name('webhooks.vodafone-cash');
Route::post('/webhooks/bank-card', [PaymentWebhookController::class, 'bankCard'])->name('webhooks.bank-card');
Route::post('/webhooks/fawry', [PaymentWebhookController::class, 'fawry'])->name('webhooks.fawry');
Route::post('/webhooks/orange-cash', [PaymentWebhookController::class, 'orangeCash'])->name('webhooks.orange-cash');
Route::post('/webhooks/instapay', [PaymentWebhookController::class, 'instaPay'])->name('webhooks.instapay');
Route::get('/cache-clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return back()->with('success', __('Cache has been cleared.'));
});

Route::get('/update', function () {
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
});

require __DIR__.'/auth.php';
