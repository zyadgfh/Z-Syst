<?php

use App\Http\Controllers as Web;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

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
    Route::get('/manual/payment', 'CustomGateway@status')->name('manual.payment');
    Route::get('payu/payment', 'Payu@view')->name('payu.view');
    Route::post('/payu/status', 'Payu@status')->name('payu.status');
    Route::post('/phonepe/status', 'PhonePe@status')->name('phonepe.status');
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

Route::get('/pharmacy', function () {
    return view('pharmacy-operations');
});

Route::get('/pharmacy/barcode-scanner', function () {
    return view('barcode-scanner');
});

Route::get('/pharmacy/pos', function () {
    return view('pharmacy-pos');
});

// Medicine Routes
Route::prefix('pharmacy/medicines')->group(function () {
    Route::get('/', [Web\MedicineController::class, 'index'])->name('pharmacy.medicines.index');
    Route::get('/create', [Web\MedicineController::class, 'create'])->name('pharmacy.medicines.create');
    Route::post('/', [Web\MedicineController::class, 'store'])->name('pharmacy.medicines.store');
    Route::get('/{id}', [Web\MedicineController::class, 'show'])->name('pharmacy.medicines.show');
    Route::get('/{id}/edit', [Web\MedicineController::class, 'edit'])->name('pharmacy.medicines.edit');
    Route::put('/{id}', [Web\MedicineController::class, 'update'])->name('pharmacy.medicines.update');
    Route::delete('/{id}', [Web\MedicineController::class, 'destroy'])->name('pharmacy.medicines.destroy');
    Route::get('/reports', [Web\MedicineController::class, 'reports'])->name('pharmacy.medicines.reports');
    Route::get('/search', [Web\MedicineController::class, 'search'])->name('pharmacy.medicines.search');
});

Route::get('/pharmacy/sales', function () {
    return view('pharmacy-sales', [
        'sales' => [
            ['invoice_number' => 'INV-1001', 'customer' => 'أحمد', 'total_amount' => 180],
            ['invoice_number' => 'INV-1002', 'customer' => 'سارة', 'total_amount' => 320],
        ],
    ]);
});

Route::get('/pharmacy/purchases', function () {
    return view('pharmacy-purchases', [
        'purchases' => [
            ['order_number' => 'PO-2001', 'supplier' => 'شركة الأدوية', 'total_amount' => 5400],
            ['order_number' => 'PO-2002', 'supplier' => 'مورد صحي', 'total_amount' => 2900],
        ],
    ]);
});

Route::get('/pharmacy-dashboard', [DashboardController::class, 'index']);

// Admin Routes - Linked to Admin Views
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('admin.dashboard');

    Route::get('/patients', function () {
        return view('admin.patients');
    })->name('admin.patients');

    Route::get('/doctors', function () {
        return view('admin.doctors');
    })->name('admin.doctors');

    Route::get('/products', function () {
        return view('admin.products');
    })->name('admin.products');

    Route::get('/purchase-orders', function () {
        return view('admin.purchase-orders');
    })->name('admin.purchase-orders');

    Route::get('/stock-transfers', function () {
        return view('admin.stock-transfers');
    })->name('admin.stock-transfers');

    Route::get('/insurance-claims', function () {
        return view('admin.insurance-claims');
    })->name('admin.insurance-claims');
});

Route::get('/docs', function () {
    return response('<!doctype html><html><head><title>Documentation</title></head><body><h1>Documentation</h1><p>Z-Syst Feature List</p></body></html>', 200);
});

Route::get('/docs/{page}', function ($page) {
    if ($page === 'z-syst-feature-list') {
        return response('<!doctype html><html><head><title>Z-Syst Feature List</title></head><body><h1>Z-Syst Product Feature List</h1><h2>Overview</h2></body></html>', 200);
    }

    abort(404);
});
