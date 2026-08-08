<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/sign-in', [Api\Auth\AuthController::class, 'login']);
        Route::post('/submit-otp', [Api\Auth\AuthController::class, 'submitOtp']);
        Route::post('/sign-up', [Api\Auth\AuthController::class, 'signUp']);
        Route::post('/resend-otp', [Api\Auth\AuthController::class, 'resendOtp']);
    });

    Route::middleware('throttle:5,5')->group(function () {
        Route::post('/send-reset-code', [Api\Auth\ZSystForgotPasswordController::class, 'sendResetCode']);
        Route::post('/verify-reset-code', [Api\Auth\ZSystForgotPasswordController::class, 'verifyResetCode']);
        Route::post('/password-reset', [Api\Auth\ZSystForgotPasswordController::class, 'resetPassword']);
    });

    Route::group(['middleware' => ['auth:sanctum']], function () {

        Route::get('summary', [Api\StatisticsController::class, 'summary']);
        Route::get('dashboard', [Api\StatisticsController::class, 'dashboard']);
        Route::get('features', [Api\FeatureStatusController::class, 'index']);

        Route::middleware(['business.context', 'throttle:20,1'])->group(function () {
            Route::post('backup', [Api\BackupController::class, 'store']);
            Route::apiResource('profile', Api\ZSystProfileController::class)->only('index', 'store');
            Route::post('change-password', [Api\ZSystProfileController::class, 'changePassword']);

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

            // Barcode API endpoints
            Route::apiResource('barcodes', Api\BarcodeController::class)->except('show');
            Route::get('barcodes/{barcode}', [Api\BarcodeController::class, 'show'])->name('barcodes.show');
            Route::post('barcodes/generate-multiple', [Api\BarcodeController::class, 'generateMultiple'])->name('barcodes.generate-multiple');
            Route::post('barcodes/generate-for-batch', [Api\BarcodeController::class, 'generateForBatch'])->name('barcodes.generate-for-batch');
            Route::post('barcodes/{barcode}/print', [Api\BarcodeController::class, 'print'])->name('barcodes.print');
            Route::post('barcodes/print-multiple', [Api\BarcodeController::class, 'printMultiple'])->name('barcodes.print-multiple');
            Route::post('barcodes/print-for-product', [Api\BarcodeController::class, 'printForProduct'])->name('barcodes.print-for-product');
            Route::post('barcodes/print-for-batch', [Api\BarcodeController::class, 'printForBatch'])->name('barcodes.print-for-batch');
            Route::post('barcodes/{barcode}/reprint', [Api\BarcodeController::class, 'reprint'])->name('barcodes.reprint');
            Route::get('barcodes/download/{filename}', [Api\BarcodeController::class, 'download'])->name('barcodes.download');
            Route::get('barcodes/search', [Api\BarcodeController::class, 'search'])->name('barcodes.search');
            Route::get('barcodes/settings', [Api\BarcodeController::class, 'settings'])->name('barcodes.settings');
            Route::get('barcodes/not-printed', [Api\BarcodeController::class, 'notPrinted'])->name('barcodes.not-printed');
            Route::get('barcodes/by-product/{productId}', [Api\BarcodeController::class, 'byProduct'])->name('barcodes.by-product');
            Route::get('barcodes/by-batch/{batchId}', [Api\BarcodeController::class, 'byBatch'])->name('barcodes.by-batch');

            // Supplier Invoice API endpoints
            Route::apiResource('supplier-invoices', Api\SupplierInvoiceController::class)->except('show');
            Route::get('supplier-invoices/{supplierInvoice}', [Api\SupplierInvoiceController::class, 'show'])->name('supplier-invoices.show');
            Route::post('supplier-invoices/{supplierInvoice}/approve', [Api\SupplierInvoiceController::class, 'approve'])->name('supplier-invoices.approve');
            Route::post('supplier-invoices/{supplierInvoice}/reject', [Api\SupplierInvoiceController::class, 'reject'])->name('supplier-invoices.reject');
            Route::post('supplier-invoices/{supplierInvoice}/cancel', [Api\SupplierInvoiceController::class, 'cancel'])->name('supplier-invoices.cancel');
            Route::post('supplier-invoices/{supplierInvoice}/add-payment', [Api\SupplierInvoiceController::class, 'addPayment'])->name('supplier-invoices.add-payment');
            Route::post('supplier-invoices/payments/{paymentId}/approve', [Api\SupplierInvoiceController::class, 'approvePayment'])->name('supplier-invoices.approve-payment');
            Route::get('supplier-invoices/pending', [Api\SupplierInvoiceController::class, 'pending'])->name('supplier-invoices.pending');
            Route::get('supplier-invoices/overdue', [Api\SupplierInvoiceController::class, 'overdue'])->name('supplier-invoices.overdue');
            Route::get('supplier-invoices/unpaid', [Api\SupplierInvoiceController::class, 'unpaid'])->name('supplier-invoices.unpaid');
            Route::get('supplier-invoices/statistics', [Api\SupplierInvoiceController::class, 'statistics'])->name('supplier-invoices.statistics');
            Route::get('supplier-invoices/aging-report', [Api\SupplierInvoiceController::class, 'agingReport'])->name('supplier-invoices.aging-report');
            Route::post('supplier-invoices/create-from-purchase', [Api\SupplierInvoiceController::class, 'createFromPurchase'])->name('supplier-invoices.create-from-purchase');

            // Doctor Attention API endpoints
            Route::get('doctor-attention/dashboard', [Api\DoctorAttentionController::class, 'dashboard'])->name('doctor-attention.dashboard');
            Route::get('doctor-attention/needing-attention', [Api\DoctorAttentionController::class, 'needingAttention'])->name('doctor-attention.needing-attention');
            Route::get('doctor-attention/critical', [Api\DoctorAttentionController::class, 'critical'])->name('doctor-attention.critical');
            Route::get('doctor-attention/alerts', [Api\DoctorAttentionController::class, 'alerts'])->name('doctor-attention.alerts');
            Route::post('doctor-attention/alerts/{alertId}/read', [Api\DoctorAttentionController::class, 'markAsRead'])->name('doctor-attention.mark-read');
            Route::post('doctor-attention/alerts/{alertId}/action', [Api\DoctorAttentionController::class, 'markAsActionTaken'])->name('doctor-attention.mark-action');
            Route::get('doctor-attention/statistics', [Api\DoctorAttentionController::class, 'statistics'])->name('doctor-attention.statistics');
            Route::put('doctor-attention/settings', [Api\DoctorAttentionController::class, 'updateSettings'])->name('doctor-attention.update-settings');
            Route::get('doctor-attention/settings', [Api\DoctorAttentionController::class, 'getSettings'])->name('doctor-attention.get-settings');
            Route::post('doctor-attention/calculate-scores', [Api\DoctorAttentionController::class, 'calculateScores'])->name('doctor-attention.calculate-scores');
            Route::post('doctor-attention/record-activity', [Api\DoctorAttentionController::class, 'recordActivity'])->name('doctor-attention.record-activity');

            // Purchase Orders API endpoints
            Route::apiResource('purchase-orders', Api\PurchaseOrderController::class)->except('show');
            Route::get('purchase-orders/{purchaseOrder}', [Api\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
            Route::post('purchase-orders/{purchaseOrder}/send', [Api\PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
            Route::post('purchase-orders/{purchaseOrder}/approve', [Api\PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
            Route::post('purchase-orders/{purchaseOrder}/reject', [Api\PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
            Route::post('purchase-orders/{purchaseOrder}/cancel', [Api\PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
            Route::post('purchase-orders/{purchaseOrder}/convert', [Api\PurchaseOrderController::class, 'convertToPurchase'])->name('purchase-orders.convert');
            Route::get('purchase-orders/pending', [Api\PurchaseOrderController::class, 'pending'])->name('purchase-orders.pending');
            Route::get('purchase-orders/overdue', [Api\PurchaseOrderController::class, 'overdue'])->name('purchase-orders.overdue');
            Route::get('purchase-orders/statistics', [Api\PurchaseOrderController::class, 'statistics'])->name('purchase-orders.statistics');

            // GRN API endpoints
            Route::apiResource('grn', Api\GRNController::class)->except('show');
            Route::get('grn/{grn}', [Api\GRNController::class, 'show'])->name('grn.show');
            Route::post('grn/{grn}/verify', [Api\GRNController::class, 'verify'])->name('grn.verify');
            Route::post('grn/{grn}/accept', [Api\GRNController::class, 'accept'])->name('grn.accept');
            Route::post('grn/{grn}/reject', [Api\GRNController::class, 'reject'])->name('grn.reject');
            Route::get('grn/pending', [Api\GRNController::class, 'pending'])->name('grn.pending');
            Route::get('grn/statistics', [Api\GRNController::class, 'statistics'])->name('grn.statistics');

            // Supplier API endpoints
            Route::apiResource('suppliers', Api\SupplierController::class)->except('show');
            Route::get('suppliers/{supplier}', [Api\SupplierController::class, 'show'])->name('suppliers.show');
            Route::post('suppliers/{supplier}/calculate-performance', [Api\SupplierController::class, 'calculatePerformance'])->name('suppliers.calculate-performance');
            Route::get('suppliers/top-performers', [Api\SupplierController::class, 'topPerformers'])->name('suppliers.top-performers');

            // Supplier Payment API endpoints
            Route::apiResource('supplier-payments', Api\SupplierPaymentController::class)->except('show');
            Route::get('supplier-payments/{supplierPayment}', [Api\SupplierPaymentController::class, 'show'])->name('supplier-payments.show');
            Route::post('supplier-payments/{supplierPayment}/approve', [Api\SupplierPaymentController::class, 'approve'])->name('supplier-payments.approve');
            Route::get('supplier-payments/generate-aging-report', [Api\SupplierPaymentController::class, 'generateAgingReport'])->name('supplier-payments.generate-aging-report');

            // Subscription API endpoints
            Route::apiResource('subscriptions', Api\SubscriptionController::class)->except('show');
            Route::get('subscriptions/{subscription}', [Api\SubscriptionController::class, 'show'])->name('subscriptions.show');
            Route::post('subscriptions/{subscription}/upgrade', [Api\SubscriptionController::class, 'upgrade'])->name('subscriptions.upgrade');
            Route::post('subscriptions/{subscription}/downgrade', [Api\SubscriptionController::class, 'downgrade'])->name('subscriptions.downgrade');
            Route::post('subscriptions/{subscription}/cancel', [Api\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
            Route::post('subscriptions/{subscription}/renew', [Api\SubscriptionController::class, 'renew'])->name('subscriptions.renew');
            Route::post('subscriptions/{subscription}/generate-invoice', [Api\SubscriptionController::class, 'generateInvoice'])->name('subscriptions.generate-invoice');
            Route::get('subscriptions/usage', [Api\SubscriptionController::class, 'usage'])->name('subscriptions.usage');
            Route::get('subscriptions/check-limits', [Api\SubscriptionController::class, 'checkLimits'])->name('subscriptions.check-limits');
        });

        Route::post('stock-update/{id}', [Api\ZSystProductController::class, 'updateStock']);
        Route::get('stocks-with-product', [Api\ZSystProductController::class, 'stocksWithProduct']);
        Route::get('dues-list', [Api\ZSystDueController::class, 'duesList']);

        Route::apiResource('parties', Api\PartyController::class);
        Route::apiResource('users', Api\ZSystUserController::class)->except('show');
        Route::apiResource('units', Api\UnitController::class)->except('show');
        Route::apiResource('categories', Api\ZSystCategoryController::class)->except('show');
        Route::apiResource('manufacturer', Api\ZSystManufacturerController::class)->except('show');
        Route::apiResource('products', Api\ZSystProductController::class);
        Route::apiResource('stocks', Api\StockController::class)->only('index');
        Route::apiResource('business-categories', Api\BusinessCategoryController::class)->only('index');
        Route::apiResource('business', Api\BusinessController::class)->only('index', 'store', 'update');
        Route::apiResource('purchase', Api\PurchaseController::class);
        Route::apiResource('sales', Api\ZSystSaleController::class);
        Route::apiResource('sales-return', Api\SaleReturnController::class)->only('index', 'store', 'show');
        Route::apiResource('purchases-return', Api\PurchaseReturnController::class)->only('index', 'store', 'show');
        Route::apiResource('invoices', Api\ZSystInvoiceController::class)->only('index');
        Route::apiResource('dues', Api\ZSystDueController::class)->only('index', 'store');
        Route::apiResource('expense-categories', Api\ExpenseCategoryController::class)->except('show');
        Route::apiResource('expenses', Api\ZSystExpenseController::class)->except('show');
        Route::apiResource('income-categories', Api\ZSystIncomeCategoryController::class)->except('show');
        Route::apiResource('incomes', Api\ZSystIncomeController::class)->except('show');
        Route::apiResource('box-sizes', Api\ZSystBoxSizeController::class)->except('show');
        Route::apiResource('medicine-types', Api\ZSystMedicineTypeController::class)->except('show');

        Route::apiResource('prescriptions', Api\ZSystPrescriptionController::class);
        Route::get('prescriptions/review', [Api\ZSystPrescriptionController::class, 'review']);
        Route::post('prescriptions/link-to-sale', [Api\ZSystPrescriptionController::class, 'linkToSale']);

        // Drug Interactions
        Route::apiResource('drug-interactions', Api\ZSystDrugInteractionController::class);
        Route::post('drug-interactions/check', [Api\ZSystDrugInteractionController::class, 'check']);
        Route::post('drug-interactions/bulk-import', [Api\ZSystDrugInteractionController::class, 'bulkImport']);

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

        Route::apiResource('banners', Api\ZSystBannerController::class)->only('index');
        Route::apiResource('lang', Api\ZSystLanguageController::class)->only('index', 'store');
        Route::apiResource('plans', Api\ZSystSubscriptionsController::class)->only('index');
        Route::apiResource('subscribes', Api\ZSystSubscribesController::class)->only('index');
        Route::apiResource('currencies', Api\ZSystCurrencyController::class)->only('index');
        Route::apiResource('taxes', Api\ZSystTaxController::class)->except('show');

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

        // Insurance System
        Route::prefix('insurance')->group(function () {
            Route::get('summary', [Api\InsuranceController::class, 'summary']);

            // Companies
            Route::get('companies', [Api\InsuranceController::class, 'companiesIndex']);
            Route::post('companies', [Api\InsuranceController::class, 'companiesStore']);
            Route::get('companies/{company}', [Api\InsuranceController::class, 'companiesShow']);
            Route::put('companies/{company}', [Api\InsuranceController::class, 'companiesUpdate']);
            Route::delete('companies/{company}', [Api\InsuranceController::class, 'companiesDestroy']);

            // Policies
            Route::get('policies', [Api\InsuranceController::class, 'policiesIndex']);
            Route::post('policies', [Api\InsuranceController::class, 'policiesStore']);
            Route::get('policies/{policy}', [Api\InsuranceController::class, 'policiesShow']);
            Route::put('policies/{policy}', [Api\InsuranceController::class, 'policiesUpdate']);
            Route::delete('policies/{policy}', [Api\InsuranceController::class, 'policiesDestroy']);

            // Claims
            Route::get('claims', [Api\InsuranceController::class, 'claimsIndex']);
            Route::post('claims', [Api\InsuranceController::class, 'claimsStore']);
            Route::get('claims/{claim}', [Api\InsuranceController::class, 'claimsShow']);
            Route::post('claims/{claim}/submit', [Api\InsuranceController::class, 'claimsSubmit']);
            Route::post('claims/{claim}/approve', [Api\InsuranceController::class, 'claimsApprove']);
            Route::post('claims/{claim}/reject', [Api\InsuranceController::class, 'claimsReject']);
            Route::post('claims/{claim}/pay', [Api\InsuranceController::class, 'claimsPay']);

            // Coverage rules
            Route::get('coverages', [Api\InsuranceController::class, 'coveragesIndex']);
            Route::post('coverages', [Api\InsuranceController::class, 'coveragesStore']);
            Route::put('coverages/{coverage}', [Api\InsuranceController::class, 'coveragesUpdate']);
            Route::delete('coverages/{coverage}', [Api\InsuranceController::class, 'coveragesDestroy']);
        });

        Route::get('new-invoice', [Api\ZSystInvoiceController::class, 'newInvoice']);
        Route::get('/sign-out', [Api\Auth\AuthController::class, 'signOut']);
        Route::get('/refresh-token', [Api\Auth\AuthController::class, 'refreshToken']);

        // Warehouse System
        Route::prefix('warehouses')->group(function () {
            Route::get('/', [Api\WarehouseController::class, 'index']);
            Route::post('/', [Api\WarehouseController::class, 'store']);
            Route::get('/{warehouse}', [Api\WarehouseController::class, 'show']);
            Route::put('/{warehouse}', [Api\WarehouseController::class, 'update']);
            Route::delete('/{warehouse}', [Api\WarehouseController::class, 'destroy']);
            Route::get('/{warehouse}/stock', [Api\WarehouseController::class, 'stock']);
        });

        // Traceability & Recall System
        Route::prefix('traceability')->group(function () {
            Route::get('batch-lots', [Api\TraceabilityController::class, 'batchLots']);
            Route::get('expiring-batches', [Api\TraceabilityController::class, 'expiringBatches']);
            Route::get('expired-batches', [Api\TraceabilityController::class, 'expiredBatches']);
            Route::get('recalls', [Api\TraceabilityController::class, 'recalls']);
            Route::post('recalls', [Api\TraceabilityController::class, 'initiateRecall']);
            Route::post('recalls/{recall}/resolve', [Api\TraceabilityController::class, 'resolveRecall']);
            Route::get('traceability', [Api\TraceabilityController::class, 'traceability']);
        });

        // Loyalty & CRM System
        Route::prefix('loyalty')->group(function () {
            Route::get('/', [Api\LoyaltyController::class, 'index']);
            Route::post('/', [Api\LoyaltyController::class, 'store']);
            Route::put('/{program}', [Api\LoyaltyController::class, 'update']);
            Route::delete('/{program}', [Api\LoyaltyController::class, 'destroy']);
            Route::get('balance/{partyId?}', [Api\LoyaltyController::class, 'partyBalance']);
            Route::get('history/{partyId?}', [Api\LoyaltyController::class, 'partyHistory']);
            Route::get('interactions', [Api\LoyaltyController::class, 'interactions']);
        });

        // Receipt System
        Route::prefix('receipts')->group(function () {
            Route::get('settings', [Api\ReceiptController::class, 'settings']);
            Route::put('settings', [Api\ReceiptController::class, 'updateSettings']);
            Route::post('sales/{sale}', [Api\ReceiptController::class, 'generateForSale']);
            Route::post('purchases/{purchase}', [Api\ReceiptController::class, 'generateForPurchase']);
            Route::get('/{receipt}', [Api\ReceiptController::class, 'show']);
            Route::get('/{receipt}/download', [Api\ReceiptController::class, 'download']);
        });
    });
});
