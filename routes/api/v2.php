<?php

use App\Http\Controllers\Api\V2\ItemController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v2 Routes — Items Module
|--------------------------------------------------------------------------
|
| Versioned API with proper pagination, field selection, and filtering.
| All routes require auth:sanctum + business.context.
|
*/

Route::middleware(['business.context', 'throttle:60,1'])->group(function () {

    // ── Items CRUD ──
    Route::get('items', [ItemController::class, 'index'])->name('v2.items.index');
    Route::post('items', [ItemController::class, 'store'])->name('v2.items.store');
    Route::get('items/{id}', [ItemController::class, 'show'])->name('v2.items.show');
    Route::put('items/{id}', [ItemController::class, 'update'])->name('v2.items.update');
    Route::delete('items/{id}', [ItemController::class, 'destroy'])->name('v2.items.destroy');

    // ── Items Search (POS / Autocomplete) ──
    Route::get('items/search', [ItemController::class, 'search'])->name('v2.items.search');

    // ── Item Stock Movements ──
    Route::get('items/{id}/stock-movements', [ItemController::class, 'stockMovements'])->name('v2.items.stock-movements');

    // ── Item KPIs ──
    Route::get('items/{id}/kpis', [ItemController::class, 'kpis'])->name('v2.items.kpis');
});
