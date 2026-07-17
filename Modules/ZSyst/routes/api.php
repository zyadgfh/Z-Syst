<?php

use Illuminate\Support\Facades\Route;
use Modules\ZSyst\App\Http\Controllers\CustomerController;
use Modules\ZSyst\App\Http\Controllers\DrugController;
use Modules\ZSyst\App\Http\Controllers\InventoryController;
use Modules\ZSyst\App\Http\Controllers\InventoryItemController;
use Modules\ZSyst\App\Http\Controllers\LoyaltyController;
use Modules\ZSyst\App\Http\Controllers\PosSaleController;
use Modules\ZSyst\App\Http\Controllers\ProcurementController;
use Modules\ZSyst\App\Http\Controllers\SupplierController;
use Modules\ZSyst\App\Http\Controllers\SyncController;

Route::get('drugs', [DrugController::class, 'index']);
Route::post('drugs', [DrugController::class, 'store']);
Route::get('drugs/{drug}', [DrugController::class, 'show']);
Route::get('drugs/barcode/{barcode}', [DrugController::class, 'findByBarcode']);
Route::get('inventory', [InventoryController::class, 'index']);
Route::post('inventory', [InventoryController::class, 'store']);
Route::get('inventory/items', [InventoryItemController::class, 'index']);
Route::post('inventory/items', [InventoryItemController::class, 'store']);
Route::get('procurement/orders', [ProcurementController::class, 'index']);
Route::post('procurement/orders', [ProcurementController::class, 'store']);
Route::get('suppliers', [SupplierController::class, 'index']);
Route::post('suppliers', [SupplierController::class, 'store']);
Route::get('customers', [CustomerController::class, 'index']);
Route::post('customers', [CustomerController::class, 'store']);
Route::get('pos/sales', [PosSaleController::class, 'index']);
Route::post('pos/sales', [PosSaleController::class, 'store']);
Route::get('pos/sales/{id}', [PosSaleController::class, 'show']);
Route::post('pos/sales/validate-inventory', [PosSaleController::class, 'validateInventory']);
Route::get('loyalty/transactions', [LoyaltyController::class, 'index']);
Route::post('loyalty/transactions', [LoyaltyController::class, 'store']);
Route::post('sync/heartbeat', [SyncController::class, 'heartbeat']);
Route::get('sync/status/{deviceId}', [SyncController::class, 'status']);
