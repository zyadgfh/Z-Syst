<?php

use App\Http\Controllers as Web;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

// Payment Routes Start
Route::get('/payments-gateways/{plan_id}/{business_id}', [Web\PaymentController::class, 'index'])->name('payments-gateways.index');
Route::post('/payments/{plan_id}/{gateway_id}', [Web\PaymentController::class, 'payment'])->name('payments-gateways.payment');
Route::get('/payment/success', [Web\PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/failed', [Web\PaymentController::class, 'failed'])->name('payment.failed');
Route::post('ssl-commerz/payment/success', [Web\PaymentController::class, 'sslCommerzSuccess']);
Route::post('ssl-commerz/payment/failed', [Web\PaymentController::class, 'sslCommerzFailed']);
Route::get('/order-status', [Web\PaymentController::class, 'orderStatus'])->name('order.status');

Route::group([
    'namespace' => 'App\Library',
], function () {
    Route::get('/payment/paypal', [Web\PaymentController::class, 'failed']);
    Route::get('/payment/mollie', [Web\PaymentController::class, 'failed']);
    Route::post('/payment/paystack', [Web\PaymentController::class, 'failed'])->name('paystack.status');
    Route::get('/paystack', [Web\PaymentController::class, 'failed'])->name('paystack.view');
    Route::get('/razorpay/payment', [Web\PaymentController::class, 'failed'])->name('razorpay.view');
    Route::post('/razorpay/status', [Web\PaymentController::class, 'failed']);
    Route::get('/mercadopago/pay', [Web\PaymentController::class, 'failed'])->name('mercadopago.status');
    Route::get('/payment/flutterwave', [Web\PaymentController::class, 'failed']);
    Route::get('/payment/thawani', [Web\PaymentController::class, 'failed']);
    Route::get('/payment/instamojo', [Web\PaymentController::class, 'failed']);
    Route::get('/payment/toyyibpay', [Web\PaymentController::class, 'failed']);
    Route::get('/manual/payment', [Web\PaymentController::class, 'failed'])->name('manual.payment');
    Route::get('payu/payment', [Web\PaymentController::class, 'failed'])->name('payu.view');
    Route::post('/payu/status', [Web\PaymentController::class, 'failed'])->name('payu.status');
    Route::post('/phonepe/status', [Web\PaymentController::class, 'failed'])->name('phonepe.status');
    Route::post('/paytm/status', [Web\PaymentController::class, 'failed'])->name('paytm.status');
    Route::get('/tap-payment/status', [Web\PaymentController::class, 'failed'])->name('tap-payment.status');
});
// Payment Routes End

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
