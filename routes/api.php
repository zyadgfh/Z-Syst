<?php

use App\Http\Controllers\Api as Api;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::post('/sign-in', [Api\Auth\AuthController::class, 'login']);
    Route::post('/submit-otp', [Api\Auth\AuthController::class, 'submitOtp']);
    Route::post('/sign-up', [Api\Auth\AuthController::class, 'signUp']);
    Route::post('/resend-otp', [Api\Auth\AuthController::class, 'resendOtp']);

    Route::post('/send-reset-code',[Api\Auth\AcnooForgotPasswordController::class, 'sendResetCode']);
    Route::post('/verify-reset-code',[Api\Auth\AcnooForgotPasswordController::class, 'verifyResetCode']);
    Route::post('/password-reset',[Api\Auth\AcnooForgotPasswordController::class, 'resetPassword']);

    Route::group(['middleware' => ['auth:sanctum']], function () {

        Route::get('summary', [Api\StatisticsController::class, 'summary']);
        Route::get('dashboard', [Api\StatisticsController::class, 'dashboard']);
        Route::get('features', [Api\FeatureStatusController::class, 'index']);
        Route::post('backup', [Api\BackupController::class, 'store']);

        Route::post('stock-update/{id}',[Api\AcnooProductController::class, 'updateStock']);
        Route::get('stocks-with-product', [Api\AcnooProductController::class, 'stocksWithProduct']);
        Route::get('dues-list', [Api\AcnooDueController::class, 'duesList']);

        Route::apiResource('parties', Api\PartyController::class);
        Route::apiResource('users', Api\AcnooUserController::class)->except('show');
        Route::apiResource('units', Api\UnitController::class)->except('show');
        Route::apiResource('categories', Api\AcnooCategoryController::class)->except('show');
        Route::apiResource('manufacturer', Api\AcnooManufacturerController::class)->except('show');
        Route::apiResource('parties', Api\PartyController::class);
        Route::apiResource('products', Api\AcnooProductController::class);
        Route::apiResource('stocks', Api\StockController::class)->only('index');
        Route::apiResource('business-categories', Api\BusinessCategoryController::class)->only('index');
        Route::apiResource('business', Api\BusinessController::class)->only('index', 'store', 'update');
        Route::apiResource('purchase', Api\PurchaseController::class);
        Route::apiResource('sales', Api\AcnooSaleController::class);
        Route::apiResource('sales-return', Api\SaleReturnController::class)->only('index', 'store', 'show');
        Route::apiResource('purchases-return', Api\PurchaseReturnController::class)->only('index', 'store', 'show');
        Route::apiResource('invoices', Api\AcnooInvoiceController::class)->only('index');
        Route::apiResource('dues', Api\AcnooDueController::class)->only('index', 'store');
        Route::apiResource('expense-categories', Api\ExpenseCategoryController::class)->except('show');
        Route::apiResource('expenses', Api\AcnooExpenseController::class)->except('show');
        Route::apiResource('income-categories', Api\AcnooIncomeCategoryController::class)->except('show');
        Route::apiResource('incomes', Api\AcnooIncomeController::class)->except('show');
        Route::apiResource('box-sizes', Api\AcnooBoxSizeController::class)->except('show');
        Route::apiResource('medicine-types', Api\AcnooMedicineTypeController::class)->except('show');

        Route::apiResource('prescriptions', Api\AcnooPrescriptionController::class);
        Route::get('prescriptions/review', [Api\AcnooPrescriptionController::class, 'review']);
        Route::post('prescriptions/link-to-sale', [Api\AcnooPrescriptionController::class, 'linkToSale']);

        // Drug Interactions
        Route::apiResource('drug-interactions', Api\AcnooDrugInteractionController::class);
        Route::post('drug-interactions/check', [Api\AcnooDrugInteractionController::class, 'check']);
        Route::post('drug-interactions/bulk-import', [Api\AcnooDrugInteractionController::class, 'bulkImport']);

        // Expiry Alerts
        Route::get('expiry-alerts/stats', [Api\ExpiryAlertController::class, 'stats']);
        Route::get('expiry-alerts', [Api\ExpiryAlertController::class, 'index']);

        // FEFO (First Expiry, First Out) System
        Route::prefix('fefo')->group(function () {
            Route::get('settings', [Api\FefoConfigController::class, 'index']);
            Route::put('settings', [Api\FefoConfigController::class, 'update']);
            Route::get('suggestions/{product}', [Api\FefoController::class, 'suggestions']);
            Route::get('product-batches/{product}', [Api\FefoController::class, 'productBatches']);
            Route::post('sale-suggestions', [Api\FefoController::class, 'saleSuggestions']);
            Route::get('report', [Api\FefoController::class, 'report']);
            Route::get('logs', [Api\FefoController::class, 'logs']);
            Route::post('remove-expired', [Api\FefoController::class, 'removeExpired']);
        });

        // AI Sales Prediction System
        Route::prefix('predictions')->group(function () {
            Route::get('settings', [Api\PredictionController::class, 'settings']);
            Route::put('settings', [Api\PredictionController::class, 'updateSettings']);
            Route::get('forecast/{product}', [Api\PredictionController::class, 'forecastProduct']);
            Route::post('batch-forecast', [Api\PredictionController::class, 'batchForecast']);
            Route::post('forecast-all', [Api\PredictionController::class, 'forecastAll']);
            Route::get('demand-report', [Api\PredictionController::class, 'demandReport']);
            Route::get('reorder-point/{product}', [Api\PredictionController::class, 'reorderPoint']);
            Route::get('forecasts/{product}', [Api\PredictionController::class, 'getForecasts']);
        });

        // Auto-Order System
        Route::prefix('auto-order')->group(function () {
            Route::get('settings', [Api\AutoOrderController::class, 'settings']);
            Route::get('settings/{product}', [Api\AutoOrderController::class, 'getRule']);
            Route::put('settings/{product}', [Api\AutoOrderController::class, 'updateRule']);
            Route::post('bulk-update-rules', [Api\AutoOrderController::class, 'bulkUpdateRules']);
            Route::post('generate', [Api\AutoOrderController::class, 'generateSuggestions']);
            Route::get('suggestions', [Api\AutoOrderController::class, 'getSuggestions']);
            Route::post('suggestions/{id}/approve', [Api\AutoOrderController::class, 'approveSuggestion']);
            Route::post('suggestions/{id}/reject', [Api\AutoOrderController::class, 'rejectSuggestion']);
            Route::post('suggestions/{id}/confirm', [Api\AutoOrderController::class, 'confirmSuggestion']);
            Route::get('report', [Api\AutoOrderController::class, 'report']);
        });

        Route::apiResource('banners', Api\AcnooBannerController::class)->only('index');
        Route::apiResource('lang', Api\AcnooLanguageController::class)->only('index', 'store');
        Route::apiResource('profile', Api\AcnooProfileController::class)->only('index', 'store');
        Route::apiResource('plans', Api\AcnooSubscriptionsController::class)->only('index');
        Route::apiResource('subscribes', Api\AcnooSubscribesController::class)->only('index');
        Route::apiResource('currencies', Api\AcnooCurrencyController::class)->only('index');
        Route::apiResource('taxes', Api\AcnooTaxController::class)->except('show');

        // Inventory Turnover Analysis
        Route::prefix('inventory-turnover')->group(function () {
            Route::get('summary', [Api\InventoryTurnoverController::class, 'summary']);
            Route::get('report', [Api\InventoryTurnoverController::class, 'report']);
            Route::get('products', [Api\InventoryTurnoverController::class, 'products']);
            Route::get('product/{product}', [Api\InventoryTurnoverController::class, 'product']);
            Route::get('slow-moving', [Api\InventoryTurnoverController::class, 'slowMoving']);
            Route::get('abc-analysis', [Api\InventoryTurnoverController::class, 'abcAnalysis']);
            Route::get('trends', [Api\InventoryTurnoverController::class, 'trends']);
        });

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

        // Stock Audit System
        Route::prefix('stock-audits')->group(function () {
            Route::get('/', [Api\StockAuditController::class, 'index']);
            Route::post('/', [Api\StockAuditController::class, 'store']);
            Route::get('{audit}', [Api\StockAuditController::class, 'show']);
            Route::post('{audit}/start', [Api\StockAuditController::class, 'start']);
            Route::post('{audit}/complete', [Api\StockAuditController::class, 'complete']);
            Route::post('{audit}/cancel', [Api\StockAuditController::class, 'cancel']);
            Route::post('{audit}/auto-populate', [Api\StockAuditController::class, 'autoPopulate']);
            Route::post('{audit}/details', [Api\StockAuditController::class, 'addDetail']);
            Route::post('{audit}/bulk-details', [Api\StockAuditController::class, 'addBulkDetails']);
            Route::get('{audit}/variance-report', [Api\StockAuditController::class, 'varianceReport']);
            Route::post('{audit}/post-all-reconciliations', [Api\StockAuditController::class, 'postAllReconciliations']);
            Route::post('details/{detail}/reconcile', [Api\StockAuditController::class, 'createReconciliation']);
            Route::post('reconciliations/{reconciliation}/post', [Api\StockAuditController::class, 'postReconciliation']);
            Route::put('reconciliations/{reconciliation}', [Api\StockAuditController::class, 'updateReconciliation']);
            Route::delete('reconciliations/{reconciliation}', [Api\StockAuditController::class, 'deleteReconciliation']);
            Route::delete('details/{detail}', [Api\StockAuditController::class, 'deleteDetail']);
        });

        // Financial Audit System
        Route::prefix('financial-audits')->group(function () {
            Route::get('/', [Api\FinancialAuditController::class, 'index']);
            Route::post('/', [Api\FinancialAuditController::class, 'store']);
            Route::get('{audit}', [Api\FinancialAuditController::class, 'show']);
            Route::post('{audit}/start', [Api\FinancialAuditController::class, 'start']);
            Route::post('{audit}/execute', [Api\FinancialAuditController::class, 'execute']);
            Route::post('{audit}/complete', [Api\FinancialAuditController::class, 'complete']);
            Route::post('{audit}/cancel', [Api\FinancialAuditController::class, 'cancel']);
            Route::get('{audit}/report', [Api\FinancialAuditController::class, 'report']);
            Route::get('{audit}/transactions', [Api\FinancialAuditController::class, 'transactionDetails']);
            Route::get('comparative-report', [Api\FinancialAuditController::class, 'comparativeReport']);
            Route::get('statistics', [Api\FinancialAuditController::class, 'statistics']);
        });

        Route::post('change-password', [Api\AcnooProfileController::class, 'changePassword']);

        Route::get('new-invoice', [Api\AcnooInvoiceController::class, 'newInvoice']);
        Route::get('/sign-out', [Api\Auth\AuthController::class, 'signOut']);
        Route::get('/refresh-token', [Api\Auth\AuthController::class, 'refreshToken']);
    });
});
