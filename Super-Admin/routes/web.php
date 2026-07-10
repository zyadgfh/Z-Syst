<?php

use App\Models\Stock;
use App\Models\Option;
use App\Models\Product;
use App\Http\Controllers as Web;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

Route::get('/', [Web\WebController::class, 'index'])->name('home');
Route::resource('blogs', Web\BlogController::class)->only('index', 'show', 'store');
Route::get('/about-us', [Web\AboutController::class, 'index'])->name('about.index');
Route::get('/plans', [Web\PlanController::class, 'index'])->name('plan.index');

// Business Signup
Route::get('/get-business-categories', [Web\AcnooBusinessController::class, 'getBusinessCategories'])->name('get-business-categories');
Route::post('/businesses', [Web\AcnooBusinessController::class, 'store'])->name('business.store');
Route::post('/verify-code', [Web\AcnooBusinessController::class, 'verifyCode'])->name('business.verify-code');

Route::get('/data-deletion', [Web\DataDeletionController::class, 'index'])->name('term.index');
Route::get('/terms-conditions', [Web\TermServiceController::class, 'index'])->name('term.index');
Route::get('/privacy-policy', [Web\PolicyController::class, 'index'])->name('policy.index');

Route::get('/contact-us', [Web\ContactController::class, 'index'])->name('contact.index');
Route::post('/contact/store', [Web\ContactController::class, 'store'])->name('contact.store');

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
    Route::get('/payment/paypal', 'Paypal@status');
    Route::get('/payment/mollie', 'Mollie@status');
    Route::post('/payment/paystack', 'Paystack@status')->name('paystack.status');
    Route::get('/paystack', 'Paystack@view')->name('paystack.view');
    Route::get('/razorpay/payment', 'Razorpay@view')->name('razorpay.view');
    Route::post('/razorpay/status', 'Razorpay@status');
    Route::get('/mercadopago/pay', 'Mercado@status')->name('mercadopago.status');
    Route::get('/payment/flutterwave', 'Flutterwave@status');
    Route::get('/payment/thawani', 'Thawani@status');
    Route::get('/payment/instamojo', 'Instamojo@status');
    Route::get('/payment/toyyibpay', 'Toyyibpay@status');
    Route::get('/phonepe/status', 'PhonePe@status')->name('phonepe.status');
    Route::post('/paytm/status', 'Paytm@status')->name('paytm.status');
    Route::get('/tap-payment/status', 'TapPayment@status')->name('tap-payment.status');
});
// Payment Routes End

Route::get('/cache-clear', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return back()->with('success', __('Cache has been cleared.'));
});

Route::get('/migrate', function () {

    Artisan::call('migrate');

    return back()->with('success', __('Migrated.'));
});

Route::get('/update', function () {
    $version = Option::where('key', 'version')->value('value') ?? null;

    // Run migrations if needed
    Artisan::call('migrate');

    if (file_exists(base_path('storage/installed'))) {
        touch(base_path('vendor/autoload1.php'));
    }

    if (Schema::hasTable('stocks') && !Stock::exists()) {
        Product::chunk(500, function ($products) {
            $data = [];
            foreach ($products as $product) {
                $data[] = [
                    'business_id' => $product->business_id,
                    'product_id'  => $product->id,
                    'expire_date' => $product->expire_date ?? null,
                    'productStock' => $product->productStock,
                    'profit_percent' => $product->profit_percent,
                    'productDealerPrice' => $product->productDealerPrice,
                    'productPurchasePrice' => $product->productPurchasePrice,
                    'productSalePrice' => $product->productSalePrice,
                    'productWholeSalePrice' => $product->productWholeSalePrice,
                    'created_at'  => $product->created_at,
                    'updated_at'  => $product->updated_at,
                ];
            }
            Stock::insert($data);
        });
    }

    // Run update file if version is not 5.5
    if ($version == 5.4) {
        $updateFile = base_path('updates/v5_5_update.php');
        if (file_exists($updateFile)) {
            require $updateFile;
            if (function_exists('runUpdate')) {
                runUpdate();
            }
        }
    }

    // Run update file for version 6.0 only
    if ($version == '5.6.2') {
        $v6_0_file = base_path('updates/v6_0_update.php');
        if (file_exists($v6_0_file)) {
            require $v6_0_file;
            if (function_exists('runUpdateForV6')) {
                runUpdateForV6();
            }
        }
    }

    // Update version
    Option::updateOrCreate(
        ['key' => 'version'],
        ['value' => '6.1']
    );

    // Clear caches
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return redirect('/')->with('message', __('System updated successfully.'));
});

require __DIR__ . '/auth.php';
