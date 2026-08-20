<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::middleware(['business.context', 'throttle:20,1'])->group(function () {
    // Profile & Password
    Route::post('backup', [Api\BackupController::class, 'store']);
    Route::apiResource('profile', Api\ZSystProfileController::class)->only('index', 'store');
    Route::post('change-password', [Api\ZSystProfileController::class, 'changePassword']);

    // Reports
    Route::get('purchase-report', [Api\ReportsController::class, 'purchaseReport']);
    Route::get('sales-report', [Api\ReportsController::class, 'salesReport']);
    Route::get('due-collects-report', [Api\ReportsController::class, 'dueCollectsReport']);
    Route::get('loss-profit-report', [Api\ReportsController::class, 'lossProfitReport']);
    Route::get('income-report', [Api\ReportsController::class, 'incomeReport']);
    Route::get('expense-report', [Api\ReportsController::class, 'expenseReport']);
    Route::get('low-stock-report', [Api\ReportsController::class, 'lowStockReport']);
    Route::get('taxes-report', [Api\ReportsController::class, 'taxesReport']);
    Route::get('sales-return-report', [Api\ReportsController::class, 'saleReturnReport']);
    Route::get('purchase-return-report', [Api\ReportsController::class, 'purchaseReturnReport']);
    Route::get('stock-audit-report', [Api\ReportsController::class, 'stockAuditReport']);
    Route::get('financial-audit-report', [Api\ReportsController::class, 'financialAuditReport']);

    // Product & Stock
    Route::post('stock-update/{id}', [Api\ZSystProductController::class, 'updateStock']);
    Route::get('stocks-with-product', [Api\ZSystProductController::class, 'stocksWithProduct']);
    Route::apiResource('products', Api\ZSystProductController::class);
    Route::apiResource('stocks', Api\StockController::class)->only('index');

    // Parties (Customers/Suppliers)
    Route::apiResource('parties', Api\PartyController::class);

    // Users
    Route::apiResource('users', Api\ZSystUserController::class)->except('show');

    // Units & Categories
    Route::apiResource('units', Api\UnitController::class)->except('show');
    Route::apiResource('categories', Api\ZSystCategoryController::class)->except('show');
    Route::apiResource('manufacturer', Api\ZSystManufacturerController::class)->except('show');
    Route::apiResource('business-categories', Api\BusinessCategoryController::class)->only('index');
    Route::apiResource('business', Api\BusinessController::class)->only('index', 'store', 'update');

    // Sales
    Route::apiResource('sales', Api\ZSystSaleController::class);
    Route::apiResource('sales-return', Api\SaleReturnController::class)->only('index', 'store', 'show');

    // Purchases
    Route::apiResource('purchase', Api\PurchaseController::class);
    Route::apiResource('purchases-return', Api\PurchaseReturnController::class)->only('index', 'store', 'show');

    // Invoices
    Route::apiResource('invoices', Api\ZSystInvoiceController::class)->only('index');
    Route::get('new-invoice', [Api\ZSystInvoiceController::class, 'newInvoice']);

    // Dues
    Route::get('dues-list', [Api\ZSystDueController::class, 'duesList']);
    Route::apiResource('dues', Api\ZSystDueController::class)->only('index', 'store');

    // Expenses & Income
    Route::apiResource('expense-categories', Api\ExpenseCategoryController::class)->except('show');
    Route::apiResource('expenses', Api\ZSystExpenseController::class)->except('show');
    Route::apiResource('income-categories', Api\ZSystIncomeCategoryController::class)->except('show');
    Route::apiResource('incomes', Api\ZSystIncomeController::class)->except('show');

    // Box Sizes & Medicine Types
    Route::apiResource('box-sizes', Api\ZSystBoxSizeController::class)->except('show');
    Route::apiResource('medicine-types', Api\ZSystMedicineTypeController::class)->except('show');

    // Prescriptions
    Route::apiResource('prescriptions', Api\ZSystPrescriptionController::class);
    Route::get('prescriptions/review', [Api\ZSystPrescriptionController::class, 'review']);
    Route::post('prescriptions/link-to-sale', [Api\ZSystPrescriptionController::class, 'linkToSale']);

    // Drug Interactions
    Route::apiResource('drug-interactions', Api\ZSystDrugInteractionController::class);
    Route::post('drug-interactions/check', [Api\ZSystDrugInteractionController::class, 'check']);
    Route::post('drug-interactions/bulk-import', [Api\ZSystDrugInteractionController::class, 'bulkImport']);

    // Taxes & Currencies
    Route::apiResource('taxes', Api\ZSystTaxController::class)->except('show');
    Route::apiResource('currencies', Api\ZSystCurrencyController::class)->only('index');

    // Banners, Languages, Plans, Subscribes
    Route::apiResource('banners', Api\ZSystBannerController::class)->only('index');
    Route::apiResource('lang', Api\ZSystLanguageController::class)->only('index', 'store');
    Route::apiResource('plans', Api\ZSystSubscriptionsController::class)->only('index');
    Route::apiResource('subscribes', Api\ZSystSubscribesController::class)->only('index');

    // Auth actions
    Route::get('/sign-out', [Api\Auth\AuthController::class, 'signOut']);
    Route::get('/refresh-token', [Api\Auth\AuthController::class, 'refreshToken']);
});
