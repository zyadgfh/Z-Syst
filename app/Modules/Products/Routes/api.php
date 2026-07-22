<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Modules\Products\Infrastructure\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Products Module API Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1/products and protected by
| auth:sanctum and tenant middleware.
|
*/

Route::prefix('v1/products')
    ->middleware(['auth:sanctum', 'tenant'])
    ->group(function () {

        // CRUD operations
        Route::get('/', [ProductController::class, 'index'])->name('products.index');
        Route::post('/', [ProductController::class, 'store'])->name('products.store');
        Route::get('search', [ProductController::class, 'search'])->name('products.search');
        Route::get('{product}', [ProductController::class, 'show'])->name('products.show');
        Route::put('{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('{product}', [ProductController::class, 'destroy'])->name('products.destroy');

        // Barcode lookup
        Route::get('barcode/{barcode}', [ProductController::class, 'byBarcode'])->name('products.barcode');

        // Stock tracking
        Route::get('expiring', [ProductController::class, 'expiring'])->name('products.expiring');
        Route::get('low-stock', [ProductController::class, 'lowStock'])->name('products.low-stock');
        Route::get('{product}/stock', [ProductController::class, 'stock'])->name('products.stock');
    });

