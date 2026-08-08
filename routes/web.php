<?php

use App\Http\Controllers as Web;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

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
Route::get('/payment/success', [Web\PaymentController::class, 'success'])->name('payment.success');
Route::get('/payment/failed', [Web\PaymentController::class, 'failed'])->name('payment.failed');
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
