<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\Sales\Infrastructure\Controllers\SaleController;

/*
|--------------------------------------------------------------------------
| Sales Module API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/sales and protected by
| auth:sanctum and tenant middleware.
|
*/

Route::prefix('v1/sales')
    ->middleware(['auth:sanctum', 'tenant'])
    ->group(function () {

        // CRUD operations
        Route::get('/', [SaleController::class, 'index'])->name('sales.index');
        Route::post('/', [SaleController::class, 'store'])->name('sales.store');
        Route::get('stats', [SaleController::class, 'stats'])->name('sales.stats');
        Route::get('{sale}', [SaleController::class, 'show'])->name('sales.show');

        // Receipt
        Route::get('{sale}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt');

        // Payments
        Route::post('{sale}/payments', [SaleController::class, 'addPayment'])->name('sales.payments');
    });

