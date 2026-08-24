<?php

use App\Models\Branch;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| This file imports all API route files organized by feature domain.
| Each file handles a specific domain (auth, ecommerce, suppliers, etc.)
|
*/

Route::prefix('v1')->group(function () {

    // Public: Authentication (login, signup, OTP, password reset)
    require __DIR__.'/api/auth.php';

    Route::group(['middleware' => ['auth:sanctum']], function () {

        // Dashboard & Features
        Route::get('summary', [App\Http\Controllers\Api\StatisticsController::class, 'summary']);
        Route::get('dashboard', [App\Http\Controllers\Api\StatisticsController::class, 'dashboard']);
        Route::get('features', [App\Http\Controllers\Api\FeatureStatusController::class, 'index']);

        // Branch API for admin interface
        Route::get('/branches/{companyId}', function ($companyId) {
            return Branch::byCompany($companyId)->active()->get();
        });

        // Core ecommerce: products, sales, purchases, parties, reports
        require __DIR__.'/api/ecommerce.php';

        // Supplier management, invoices, payments, GRN, purchase orders
        require __DIR__.'/api/suppliers.php';

        // Subscriptions, Supabase auth/storage
        require __DIR__.'/api/subscriptions.php';

        // POS payments
        require __DIR__.'/api/payments.php';

        // E-invoicing, marketing automation, tenant onboarding
        require __DIR__.'/api/einvoicing.php';

        // Advanced features: FEFO, predictions, auto-order, audits, insurance, warehouses, etc.
        require __DIR__.'/api/advanced.php';
    });
});

// ── API v2: Items Module ──
Route::prefix('v2')->group(function () {
    Route::group(['middleware' => ['auth:sanctum']], function () {
        require __DIR__.'/api/v2.php';
    });
});
