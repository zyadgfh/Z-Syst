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

    // Health check (public, no auth required)
    Route::get('/health', function () {
        $checks = [
            'app' => 'ok',
            'database' => 'unknown',
            'cache' => 'unknown',
            'queue' => 'unknown',
        ];

        try {
            \Illuminate\Support\Facades\DB::select('SELECT 1');
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'error: ' . $e->getMessage();
        }

        try {
            \Illuminate\Support\Facades\Cache::put('health_check', true, 10);
            $checks['cache'] = 'ok';
        } catch (\Exception $e) {
            $checks['cache'] = 'error: ' . $e->getMessage();
        }

        try {
            \Illuminate\Support\Facades\Queue::size();
            $checks['queue'] = 'ok';
        } catch (\Exception $e) {
            $checks['queue'] = 'error: ' . $e->getMessage();
        }

        $allOk = !in_array('error: ' , array_map(fn($v) => str_starts_with($v, 'error:') ? $v : '', $checks));

        return response()->json([
            'status' => $allOk ? 'healthy' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
        ], $allOk ? 200 : 503);
    });

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

        // Push Notifications (FCM)
        Route::post('push-tokens', [App\Http\Controllers\Api\PushTokenController::class, 'store']);
        Route::delete('push-tokens', [App\Http\Controllers\Api\PushTokenController::class, 'destroy']);
        Route::post('push-tokens/test', [App\Http\Controllers\Api\PushTokenController::class, 'test']);

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
