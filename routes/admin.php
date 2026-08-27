<?php

use App\Http\Controllers\Admin as ADMIN;
use Illuminate\Support\Facades\Route;

Route::group(['as' => 'admin.', 'prefix' => 'admin', 'middleware' => ['auth', 'admin', 'clerk.auth']], function () {
    Route::get('/', [ADMIN\DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/get-dashboard', [ADMIN\DashboardController::class, 'getDashboardData'])->name('dashboard.data');
    Route::get('/yearly-subscriptions', [ADMIN\DashboardController::class, 'yearlySubscriptions'])->name('dashboard.subscriptions');
    Route::get('/plans-overview', [ADMIN\DashboardController::class, 'subscriptionPlan'])->name('dashboard.plans-overview');
    
    // Design System Dashboard
    Route::get('/dashboard/design-system', [ADMIN\DashboardController::class, 'designSystem'])->name('dashboard.design-system');
    
    // Analytics - design system view
    Route::get('/analytics', [ADMIN\AnalyticsController::class, 'index'])->name('analytics.index');

    Route::resource('users', ADMIN\UserController::class)->except('show');
    Route::post('users/filter', [ADMIN\UserController::class, 'zsystFilter'])->name('users.filter');
    Route::post('users/status/{id}', [ADMIN\UserController::class, 'status'])->name('users.status');
    Route::post('users/delete-all', [ADMIN\UserController::class, 'deleteAll'])->name('users.delete-all');
    Route::post('users/bulk-change-status', [ADMIN\UserController::class, 'bulkChangeStatus'])->name('users.bulk-change-status');
    Route::get('users/statistics', [ADMIN\UserController::class, 'statistics'])->name('users.statistics');
    Route::get('users/role-statistics', [ADMIN\UserController::class, 'roleStatistics'])->name('users.role-statistics');
    Route::get('users/permissions-grouped', [ADMIN\UserController::class, 'permissionsGrouped'])->name('users.permissions-grouped');
    Route::get('users/{user}/permissions', [ADMIN\UserController::class, 'userPermissions'])->name('users.permissions');
    Route::post('users/clone-permissions', [ADMIN\UserController::class, 'clonePermissions'])->name('users.clone-permissions');
    Route::get('users-excel', [ADMIN\UserController::class, 'exportExcel'])->name('users.excel');
    Route::get('users-csv', [ADMIN\UserController::class, 'exportCsv'])->name('users.csv');

    Route::resource('banners', ADMIN\ZSystBannerController::class)->except('show', 'edit', 'create');
    Route::post('banners/filter', [ADMIN\ZSystBannerController::class, 'zsystFilter'])->name('banners.filter');
    Route::post('banners/status/{id}', [ADMIN\ZSystBannerController::class, 'status'])->name('banners.status');
    Route::post('banners/delete-all', [ADMIN\ZSystBannerController::class, 'deleteAll'])->name('banners.delete-all');
    Route::get('banner/excel', [ADMIN\ZSystBannerController::class, 'exportExcel'])->name('banners.excel');
    Route::get('banner/csv', [ADMIN\ZSystBannerController::class, 'exportCsv'])->name('banners.csv');

    // Subscription Plans
    Route::resource('plans', ADMIN\ZSystPlanController::class)->except('show');
    Route::post('plans/filter', [ADMIN\ZSystPlanController::class, 'zsystFilter'])->name('plans.filter');
    Route::post('plans/status/{id}', [ADMIN\ZSystPlanController::class, 'status'])->name('plans.status');
    Route::post('plans/delete-all', [ADMIN\ZSystPlanController::class, 'deleteAll'])->name('plans.delete-all');
    Route::get('plans-excel', [ADMIN\ZSystPlanController::class, 'exportExcel'])->name('plans.excel');
    Route::get('plans-csv', [ADMIN\ZSystPlanController::class, 'exportCsv'])->name('plans.csv');
    Route::get('plans/statistics', [ADMIN\ZSystPlanController::class, 'statistics'])->name('plans.statistics');
    Route::get('plans/popular', [ADMIN\ZSystPlanController::class, 'popularPlans'])->name('plans.popular');
    Route::get('plans/{plan}/usage', [ADMIN\ZSystPlanController::class, 'planUsage'])->name('plans.usage');
    Route::post('plans/calculate-proration', [ADMIN\ZSystPlanController::class, 'calculateProration'])->name('plans.calculate-proration');

    // Business
    Route::resource('business', ADMIN\ZSystBusinessController::class);
    Route::put('business/upgrade-plan/{id}', [ADMIN\ZSystBusinessController::class, 'upgradePlan'])->name('business.upgrade.plan');
    Route::post('business/filter', [ADMIN\ZSystBusinessController::class, 'zsystFilter'])->name('business.filter');
    Route::post('business/status/{id}', [ADMIN\ZSystBusinessController::class, 'status'])->name('business.status');
    Route::post('business/delete-all', [ADMIN\ZSystBusinessController::class, 'deleteAll'])->name('business.delete-all');
    Route::get('business-excel', [ADMIN\ZSystBusinessController::class, 'exportExcel'])->name('business.excel');
    Route::get('business-csv', [ADMIN\ZSystBusinessController::class, 'exportCsv'])->name('business.csv');

    // Business Categories
    Route::resource('business-categories', ADMIN\ZSystBusinessCategoryController::class)->except('show');
    Route::post('business-category/filter', [ADMIN\ZSystBusinessCategoryController::class, 'zsystFilter'])->name('business-categories.filter');
    Route::post('business-categories/status/{id}', [ADMIN\ZSystBusinessCategoryController::class, 'status'])->name('business-categories.status');
    Route::post('business-categories/delete-all', [ADMIN\ZSystBusinessCategoryController::class, 'deleteAll'])->name('business-categories.delete-all');
    Route::get('business-categories-excel', [ADMIN\ZSystBusinessCategoryController::class, 'exportExcel'])->name('business-categories.excel');
    Route::get('business-categories-csv', [ADMIN\ZSystBusinessCategoryController::class, 'exportCsv'])->name('business-categories.csv');

    Route::resource('profiles', ADMIN\ProfileController::class)->only('index', 'update');

    Route::resource('subscription-reports', ADMIN\SubscriptionReport::class)->only('index');
    Route::post('subscription-reports/filter', [ADMIN\SubscriptionReport::class, 'zsystFilter'])->name('subscription-reports.filter');
    Route::post('subscription-reports/reject/{id}', [ADMIN\SubscriptionReport::class, 'reject'])->name('subscription-reports.reject');
    Route::post('subscription-reports/paid/{id}', [ADMIN\SubscriptionReport::class, 'paid'])->name('subscription-reports.paid');
    Route::get('subscription-report/get-invoice/{id}', [ADMIN\SubscriptionReport::class, 'getInvoice'])->name('subscription-reports.invoice');
    Route::get('subscription-reports-excel', [ADMIN\SubscriptionReport::class, 'exportExcel'])->name('subscription-reports.excel');
    Route::get('subscription-reports-csv', [ADMIN\SubscriptionReport::class, 'exportCsv'])->name('subscription-reports.csv');

    Route::resource('manual-payments', ADMIN\ZSystManualPaymentReportController::class)->only('index');
    Route::post('manual-payments/filter', [ADMIN\ZSystManualPaymentReportController::class, 'zsystFilter'])->name('manual-payments.filter');
    Route::post('manual-payments/reject/{id}', [ADMIN\ZSystManualPaymentReportController::class, 'reject'])->name('manual-payments.reject');
    Route::post('manual-payments/paid/{id}', [ADMIN\ZSystManualPaymentReportController::class, 'paid'])->name('manual-payments.paid');
    Route::get('manual-payments/get-invoice/{id}', [ADMIN\ZSystManualPaymentReportController::class, 'getInvoice'])->name('manual-payments.invoice');
    Route::get('manual-payments-excel', [ADMIN\ZSystManualPaymentReportController::class, 'exportExcel'])->name('manual-payments.excel');
    Route::get('manual-payments-csv', [ADMIN\ZSystManualPaymentReportController::class, 'exportCsv'])->name('manual-payments.csv');

    // Expired Business
    Route::resource('expired-business', ADMIN\ZSystExpireBusinessReportController::class)->only('index');
    Route::post('expired-business/filter', [ADMIN\ZSystExpireBusinessReportController::class, 'zsystFilter'])->name('expired-business.filter');
    Route::get('expired-business-excel', [ADMIN\ZSystExpireBusinessReportController::class, 'exportExcel'])->name('expired-business.excel');
    Route::get('expired-business-csv', [ADMIN\ZSystExpireBusinessReportController::class, 'exportCsv'])->name('expired-business.csv');

    // Active Business
    Route::resource('active-stores', ADMIN\ZSystActiveBusinessReportController::class)->only('index');
    Route::post('active-stores/filter', [ADMIN\ZSystActiveBusinessReportController::class, 'zsystFilter'])->name('active-stores.filter');
    Route::get('active-stores-excel', [ADMIN\ZSystActiveBusinessReportController::class, 'exportExcel'])->name('active-stores.excel');
    Route::get('active-stores-csv', [ADMIN\ZSystActiveBusinessReportController::class, 'exportCsv'])->name('active-stores.csv');

    // Roles & Permissions
    Route::resource('roles', ADMIN\RoleController::class)->except('show');
    Route::resource('permissions', ADMIN\PermissionController::class)->only('index', 'store');

    // Settings (legacy)
    Route::resource('settings', ADMIN\SettingController::class)->only('index', 'update');
    Route::resource('system-settings', ADMIN\SystemSettingController::class)->only('index', 'store');

    // Application Settings & User Preferences
    Route::get('app-settings', [ADMIN\SettingsController::class, 'index'])->name('app-settings.index');
    Route::get('app-settings/module/{module}', [ADMIN\SettingsController::class, 'getModuleSettings'])->name('app-settings.module');
    Route::post('app-settings/update-system', [ADMIN\SettingsController::class, 'updateSystem'])->name('app-settings.update-system');
    Route::post('app-settings/update-organization', [ADMIN\SettingsController::class, 'updateOrganization'])->name('app-settings.update-organization');
    Route::post('app-settings/update-branch', [ADMIN\SettingsController::class, 'updateBranch'])->name('app-settings.update-branch');
    Route::post('app-settings/update-role', [ADMIN\SettingsController::class, 'updateRole'])->name('app-settings.update-role');
    Route::post('app-settings/update-user', [ADMIN\SettingsController::class, 'updateUser'])->name('app-settings.update-user');
    Route::post('app-settings/update-bulk', [ADMIN\SettingsController::class, 'updateBulk'])->name('app-settings.update-bulk');
    Route::post('app-settings/reset', [ADMIN\SettingsController::class, 'resetToInherited'])->name('app-settings.reset');
    Route::post('app-settings/search', [ADMIN\SettingsController::class, 'search'])->name('app-settings.search');
    Route::get('app-settings/audit-log', [ADMIN\SettingsController::class, 'getAuditLog'])->name('app-settings.audit-log');
    Route::get('app-settings/definitions', [ADMIN\SettingsController::class, 'getDefinitions'])->name('app-settings.definitions');
    Route::get('app-settings/effective', [ADMIN\SettingsController::class, 'getEffective'])->name('app-settings.effective');
    Route::get('app-settings/module-meta', [ADMIN\SettingsController::class, 'getModuleMeta'])->name('app-settings.module-meta');
    Route::post('app-settings/seed-defaults', [ADMIN\SettingsController::class, 'seedDefaults'])->name('app-settings.seed-defaults');

    // Branch Settings (shortcut)
    Route::get('branches/{branch}/settings', function ($branch) {
        return redirect()->route('admin.app-settings.index', ['scope_type' => 'branch', 'scope_id' => $branch]);
    })->name('branches.settings');

    // Items / Products Management Module
    Route::get('items', [ADMIN\ProductController::class, 'index'])->name('items.index');
    Route::get('items/create', [ADMIN\ProductController::class, 'create'])->name('items.create');
    Route::post('items/store', [ADMIN\ProductController::class, 'store'])->name('items.store');
    Route::get('items/{id}', [ADMIN\ProductController::class, 'show'])->name('items.show');
    Route::get('items/{id}/edit', [ADMIN\ProductController::class, 'edit'])->name('items.edit');
    Route::put('items/{product}/update', [ADMIN\ProductController::class, 'update'])->name('items.update');
    Route::delete('items/{product}', [ADMIN\ProductController::class, 'destroy'])->name('items.destroy');
    Route::get('items/search', [ADMIN\ProductController::class, 'search'])->name('items.search');
    Route::get('items/export', [ADMIN\ProductController::class, 'export'])->name('items.export');
    Route::get('items/generate-code', [ADMIN\ProductController::class, 'generateInternalCode'])->name('items.generate-code');
    Route::get('items/statistics', [ADMIN\ProductController::class, 'statistics'])->name('items.statistics');
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('items/{id}/stock-adjust', [ADMIN\ProductController::class, 'stockAdjust'])->name('items.stock-adjust');
    });

    // Sensitive items actions — rate-limited to prevent abuse
    Route::middleware('throttle:30,1')->group(function () {
        Route::post('items/check-duplicates', [ADMIN\ProductController::class, 'checkDuplicates'])->name('items.check-duplicates');
        Route::post('items/search-barcode', [ADMIN\ProductController::class, 'searchByBarcode'])->name('items.search-barcode');
        Route::post('items/{id}/print-barcode', [ADMIN\ProductController::class, 'printBarcode'])->name('items.print-barcode');
    });
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('items/bulk-update', [ADMIN\ProductController::class, 'bulkUpdate'])->name('items.bulk-update');
        Route::post('items/import', [ADMIN\ProductController::class, 'import'])->name('items.import');
    });

    // Legacy Products routes (redirect to items)
    Route::get('products', function () {
        return redirect()->route('admin.items.index');
    })->name('products.index');

    // Purchase Invoices (Admin Web)
    Route::get('purchases', [ADMIN\PurchaseInvoiceController::class, 'index'])->name('purchases.index');
    Route::get('purchases/create', [ADMIN\PurchaseInvoiceController::class, 'create'])->name('purchases.create');
    Route::post('purchases/store-ajax', [ADMIN\PurchaseInvoiceController::class, 'storeAjax'])->name('purchases.store-ajax');
    Route::get('purchases/{purchase}', [ADMIN\PurchaseInvoiceController::class, 'show'])->name('purchases.show');
    Route::get('purchases/{purchase}/edit', [ADMIN\PurchaseInvoiceController::class, 'edit'])->name('purchases.edit');
    Route::put('purchases/{purchase}/update-ajax', [ADMIN\PurchaseInvoiceController::class, 'updateAjax'])->name('purchases.update-ajax');
    Route::post('purchases/{purchase}/cancel', [ADMIN\PurchaseInvoiceController::class, 'cancel'])->name('purchases.cancel');
    Route::post('purchases/search-barcode', [ADMIN\PurchaseInvoiceController::class, 'searchBarcode'])->name('purchases.search-barcode');
    Route::post('purchases/search-products', [ADMIN\PurchaseInvoiceController::class, 'searchProducts'])->name('purchases.search-products');
    Route::get('purchases/statistics', [ADMIN\PurchaseInvoiceController::class, 'statistics'])->name('purchases.statistics');

    // Purchase Returns (Admin Web)
    Route::get('purchases/returns', [ADMIN\PurchaseReturnController::class, 'index'])->name('purchases.returns.index');
    Route::get('purchases/returns/create', [ADMIN\PurchaseReturnController::class, 'create'])->name('purchases.returns.create');
    Route::post('purchases/returns/store', [ADMIN\PurchaseReturnController::class, 'store'])->name('purchases.returns.store');
    Route::get('purchases/returns/{purchaseReturn}', [ADMIN\PurchaseReturnController::class, 'show'])->name('purchases.returns.show');

    // Supplier Dashboard
    Route::get('suppliers/{supplier}/dashboard', [ADMIN\SupplierDashboardController::class, 'index'])->name('suppliers.dashboard');
    Route::get('suppliers/{supplier}/ledger', [ADMIN\SupplierDashboardController::class, 'ledger'])->name('suppliers.ledger');
    Route::post('suppliers/{supplier}/payment', [ADMIN\SupplierDashboardController::class, 'recordPayment'])->name('suppliers.payment');

    // Purchase Reports
    Route::get('purchases/reports', [ADMIN\PurchaseReportController::class, 'index'])->name('purchases.reports');
    Route::get('purchases/reports/supplier-balance', [ADMIN\PurchaseReportController::class, 'supplierBalance'])->name('purchases.supplier-balance');
    Route::get('purchases/reports/stock-movements', [ADMIN\PurchaseReportController::class, 'stockMovements'])->name('purchases.stock-movements');

    // Security Dashboard
    Route::get('security-dashboard', [ADMIN\SecurityDashboardController::class, 'index'])->name('security-dashboard.index');

    // Vulnerability Exception Tracking
    Route::resource('vulnerability-exceptions', ADMIN\VulnerabilityExceptionController::class)->except('edit', 'update', 'destroy');
    Route::post('vulnerability-exceptions/{vulnerabilityException}/approve', [ADMIN\VulnerabilityExceptionController::class, 'approve'])->name('vulnerability-exceptions.approve');
    Route::post('vulnerability-exceptions/{vulnerabilityException}/reject', [ADMIN\VulnerabilityExceptionController::class, 'reject'])->name('vulnerability-exceptions.reject');
    Route::post('vulnerability-exceptions/{vulnerabilityException}/revoke', [ADMIN\VulnerabilityExceptionController::class, 'revoke'])->name('vulnerability-exceptions.revoke');
    Route::post('vulnerability-exceptions/process-expirations', [ADMIN\VulnerabilityExceptionController::class, 'processExpirations'])->name('vulnerability-exceptions.process-expirations');
    Route::get('vulnerability-exceptions/statistics', [ADMIN\VulnerabilityExceptionController::class, 'statistics'])->name('vulnerability-exceptions.statistics');

    // Maintenance Mode
    Route::get('maintenance', [ADMIN\MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('maintenance/status', [ADMIN\MaintenanceController::class, 'getStatus'])->name('maintenance.status');
    Route::get('maintenance/history', [ADMIN\MaintenanceController::class, 'history'])->name('maintenance.history');
    
    // Maintenance Mode Actions (Super Admin Only)
    Route::middleware(['role:superadmin'])->group(function () {
        Route::post('maintenance/activate', [ADMIN\MaintenanceController::class, 'activate'])->name('maintenance.activate');
        Route::post('maintenance/deactivate', [ADMIN\MaintenanceController::class, 'deactivate'])->name('maintenance.deactivate');
        Route::post('maintenance/schedule', [ADMIN\MaintenanceController::class, 'schedule'])->name('maintenance.schedule');
        Route::put('maintenance/{id}', [ADMIN\MaintenanceController::class, 'update'])->name('maintenance.update');
    });

    // Gateway
    Route::resource('gateways', ADMIN\GatewayController::class)->only('index', 'update');

    // Payment Gateways (Multi-tenant Egyptian payment gateways)
    Route::resource('payment-gateways', ADMIN\PaymentGatewayController::class)->except('show');
    Route::post('payment-gateways/toggle-status/{id}', [ADMIN\PaymentGatewayController::class, 'toggleStatus'])->name('payment-gateways.toggle-status');
    Route::get('payment-gateways/{id}/transactions', [ADMIN\PaymentGatewayController::class, 'transactions'])->name('payment-gateways.transactions');
    Route::get('payment-gateways/get-required-fields', [ADMIN\PaymentGatewayController::class, 'getRequiredFields'])->name('payment-gateways.get-required-fields');
    Route::post('payment-gateways/test-configuration', [ADMIN\PaymentGatewayController::class, 'testConfiguration'])->name('payment-gateways.test-configuration');

    Route::resource('currencies', ADMIN\ZSystCurrencyController::class)->except('show');
    Route::post('currencies/filter', [ADMIN\ZSystCurrencyController::class, 'zsystFilter'])->name('currencies.filter');
    Route::match(['get', 'post'], 'currencies/default/{id}', [ADMIN\ZSystCurrencyController::class, 'default'])->name('currencies.default');
    Route::post('currencies/delete-all', [ADMIN\ZSystCurrencyController::class, 'deleteAll'])->name('currencies.delete-all');
    Route::get('currencies-excel', [ADMIN\ZSystCurrencyController::class, 'exportExcel'])->name('currencies.excel');
    Route::get('currencies-csv', [ADMIN\ZSystCurrencyController::class, 'exportCsv'])->name('currencies.csv');

    // Prescriptions
    Route::resource('prescriptions', ADMIN\ZSystPrescriptionController::class)->except('show', 'edit', 'create');
    Route::post('prescriptions/filter', [ADMIN\ZSystPrescriptionController::class, 'zsystFilter'])->name('prescriptions.filter');
    Route::post('prescriptions/status/{id}', [ADMIN\ZSystPrescriptionController::class, 'status'])->name('prescriptions.status');
    Route::post('prescriptions/delete-all', [ADMIN\ZSystPrescriptionController::class, 'deleteAll'])->name('prescriptions.delete-all');
    Route::post('prescriptions/link-to-sale/{id}', [ADMIN\ZSystPrescriptionController::class, 'linkToSale'])->name('prescriptions.link-to-sale');

    // Notifications manager
    Route::prefix('notifications')->controller(ADMIN\NotificationController::class)->name('notifications.')->group(function () {
        Route::get('/', 'mtIndex')->name('index');
        Route::get('/{id}', 'mtView')->name('mtView');
        Route::get('view/all/', 'mtReadAll')->name('mtReadAll');
    });

    // Warehouses
    Route::resource('warehouses', ADMIN\WarehouseController::class)->except('show');
    Route::get('warehouses/{warehouse}', [ADMIN\WarehouseController::class, 'show'])->name('warehouses.show');
    Route::post('warehouses/{warehouse}/set-default', [ADMIN\WarehouseController::class, 'setDefault'])->name('warehouses.set-default');
    Route::post('warehouses/{warehouse}/add-stock', [ADMIN\WarehouseController::class, 'addStock'])->name('warehouses.add-stock');
    Route::post('warehouses/{warehouse}/remove-stock', [ADMIN\WarehouseController::class, 'removeStock'])->name('warehouses.remove-stock');
    Route::get('warehouses/statistics', [ADMIN\WarehouseController::class, 'statistics'])->name('warehouses.statistics');

    // Stock Transfers
    Route::resource('stock-transfers', ADMIN\StockTransferController::class)->except('show');
    Route::get('stock-transfers/{transfer}', [ADMIN\StockTransferController::class, 'show'])->name('stock-transfers.show');
    Route::post('stock-transfers/{transfer}/complete', [ADMIN\StockTransferController::class, 'complete'])->name('stock-transfers.complete');
    Route::post('stock-transfers/{transfer}/cancel', [ADMIN\StockTransferController::class, 'cancel'])->name('stock-transfers.cancel');
    Route::get('stock-transfers/statistics', [ADMIN\StockTransferController::class, 'statistics'])->name('stock-transfers.statistics');
    Route::get('stock-transfers/stock-distribution', [ADMIN\StockTransferController::class, 'stockDistribution'])->name('stock-transfers.stock-distribution');

    // Barcodes
    Route::resource('barcodes', ADMIN\BarcodeController::class)->except('show');
    Route::get('barcodes/{barcode}', [ADMIN\BarcodeController::class, 'show'])->name('barcodes.show');
    Route::post('barcodes/generate-multiple', [ADMIN\BarcodeController::class, 'generateMultiple'])->name('barcodes.generate-multiple');
    Route::post('barcodes/generate-for-batch', [ADMIN\BarcodeController::class, 'generateForBatch'])->name('barcodes.generate-for-batch');
    Route::post('barcodes/{barcode}/print', [ADMIN\BarcodeController::class, 'print'])->name('barcodes.print');
    Route::post('barcodes/print-multiple', [ADMIN\BarcodeController::class, 'printMultiple'])->name('barcodes.print-multiple');
    Route::post('barcodes/print-for-product', [ADMIN\BarcodeController::class, 'printForProduct'])->name('barcodes.print-for-product');
    Route::post('barcodes/print-for-batch', [ADMIN\BarcodeController::class, 'printForBatch'])->name('barcodes.print-for-batch');
    Route::post('barcodes/{barcode}/reprint', [ADMIN\BarcodeController::class, 'reprint'])->name('barcodes.reprint');
    Route::get('barcodes/download/{filename}', [ADMIN\BarcodeController::class, 'download'])->name('barcodes.download');
    Route::get('barcodes/search', [ADMIN\BarcodeController::class, 'search'])->name('barcodes.search');
    Route::get('barcodes/settings', [ADMIN\BarcodeController::class, 'settings'])->name('barcodes.settings');
    Route::get('barcodes/not-printed', [ADMIN\BarcodeController::class, 'notPrinted'])->name('barcodes.not-printed');
    Route::get('barcodes/by-product/{productId}', [ADMIN\BarcodeController::class, 'byProduct'])->name('barcodes.by-product');
    Route::get('barcodes/by-batch/{batchId}', [ADMIN\BarcodeController::class, 'byBatch'])->name('barcodes.by-batch');
    Route::post('barcodes/{barcodeId}/restore', [ADMIN\BarcodeController::class, 'restore'])->name('barcodes.restore');

    // Traceability & Recall
    Route::get('traceability', [ADMIN\TraceabilityController::class, 'index'])->name('traceability.index');
    Route::get('traceability/batch-lots', [ADMIN\TraceabilityController::class, 'batchLots'])->name('traceability.batch-lots');
    Route::post('traceability/batch-lots', [ADMIN\TraceabilityController::class, 'createBatchLot'])->name('traceability.create-batch-lot');
    Route::put('traceability/batch-lots/{batchLot}', [ADMIN\TraceabilityController::class, 'updateBatchLot'])->name('traceability.update-batch-lot');
    Route::delete('traceability/batch-lots/{batchLot}', [ADMIN\TraceabilityController::class, 'destroyBatchLot'])->name('traceability.destroy-batch-lot');
    Route::get('traceability/recalls', [ADMIN\TraceabilityController::class, 'recalls'])->name('traceability.recalls');
    Route::post('traceability/recalls', [ADMIN\TraceabilityController::class, 'initiateRecall'])->name('traceability.initiate-recall');
    Route::post('traceability/recalls/{recall}/resolve', [ADMIN\TraceabilityController::class, 'resolveRecall'])->name('traceability.resolve-recall');
    Route::delete('traceability/recalls/{recall}', [ADMIN\TraceabilityController::class, 'destroyRecall'])->name('traceability.destroy-recall');
    Route::get('traceability/product-traceability', [ADMIN\TraceabilityController::class, 'getProductTraceability'])->name('traceability.product-traceability');
    Route::get('traceability/statistics', [ADMIN\TraceabilityController::class, 'statistics'])->name('traceability.statistics');
    Route::get('traceability/recall-statistics', [ADMIN\TraceabilityController::class, 'recallStatistics'])->name('traceability.recall-statistics');
    Route::get('traceability/expiring-batches', [ADMIN\TraceabilityController::class, 'expiringBatches'])->name('traceability.expiring-batches');
    Route::get('traceability/expired-batches', [ADMIN\TraceabilityController::class, 'expiredBatches'])->name('traceability.expired-batches');
    Route::get('traceability/recalls/{recall}/summary', [ADMIN\TraceabilityController::class, 'recallSummary'])->name('traceability.recall-summary');
    Route::post('traceability/recalls/{recall}/quarantine', [ADMIN\TraceabilityController::class, 'quarantineBatch'])->name('traceability.quarantine-batch');
    Route::post('traceability/recalls/{recall}/release', [ADMIN\TraceabilityController::class, 'releaseBatch'])->name('traceability.release-batch');
    Route::post('traceability/recalls/{recall}/dispose', [ADMIN\TraceabilityController::class, 'disposeBatch'])->name('traceability.dispose-batch');
    Route::get('traceability/detect-affected', [ADMIN\TraceabilityController::class, 'detectAffectedBatches'])->name('traceability.detect-affected');

    // Loyalty & CRM
    Route::get('loyalty', [ADMIN\LoyaltyController::class, 'index'])->name('loyalty.index');
    Route::get('loyalty/programs', [ADMIN\LoyaltyController::class, 'programs'])->name('loyalty.programs');
    Route::post('loyalty/programs', [ADMIN\LoyaltyController::class, 'store'])->name('loyalty.store');
    Route::get('loyalty/create', [ADMIN\LoyaltyController::class, 'create'])->name('loyalty.create');
    Route::get('loyalty/{program}/edit', [ADMIN\LoyaltyController::class, 'edit'])->name('loyalty.edit');
    Route::put('loyalty/{program}', [ADMIN\LoyaltyController::class, 'update'])->name('loyalty.update');
    Route::delete('loyalty/{program}', [ADMIN\LoyaltyController::class, 'destroy'])->name('loyalty.destroy');
    Route::get('loyalty/transactions', [ADMIN\LoyaltyController::class, 'transactions'])->name('loyalty.transactions');
    Route::get('loyalty/interactions', [ADMIN\LoyaltyController::class, 'interactions'])->name('loyalty.interactions');
    Route::post('loyalty/interactions', [ADMIN\LoyaltyController::class, 'createInteraction'])->name('loyalty.create-interaction');
    Route::get('loyalty/statistics', [ADMIN\LoyaltyController::class, 'statistics'])->name('loyalty.statistics');
    Route::get('loyalty/crm-statistics', [ADMIN\LoyaltyController::class, 'crmStatistics'])->name('loyalty.crm-statistics');
    Route::get('loyalty/customer-loyalty', [ADMIN\LoyaltyController::class, 'customerLoyalty'])->name('loyalty.customer-loyalty');
    Route::get('loyalty/customer-interactions', [ADMIN\LoyaltyController::class, 'customerInteractions'])->name('loyalty.customer-interactions');
    Route::get('loyalty/top-loyal-customers', [ADMIN\LoyaltyController::class, 'topLoyalCustomers'])->name('loyalty.top-loyal-customers');
    Route::get('loyalty/analytics', [ADMIN\LoyaltyController::class, 'analytics'])->name('loyalty.analytics');
    Route::get('loyalty/expiring-soonest', [ADMIN\LoyaltyController::class, 'expiringSoonest'])->name('loyalty.expiring-soonest');
    Route::post('loyalty/send-expiry-reminder', [ADMIN\LoyaltyController::class, 'sendExpiryReminder'])->name('loyalty.send-expiry-reminder');

    // Receipts
    Route::get('receipts', [ADMIN\ReceiptController::class, 'index'])->name('receipts.index');
    Route::get('receipts/settings', [ADMIN\ReceiptController::class, 'settings'])->name('receipts.settings');
    Route::put('receipts/settings', [ADMIN\ReceiptController::class, 'updateSettings'])->name('receipts.update-settings');
    Route::get('receipts/{receipt}', [ADMIN\ReceiptController::class, 'show'])->name('receipts.show');
    Route::post('receipts/generate-sale', [ADMIN\ReceiptController::class, 'generateSale'])->name('receipts.generate-sale');
    Route::post('receipts/generate-purchase', [ADMIN\ReceiptController::class, 'generatePurchase'])->name('receipts.generate-purchase');
    Route::get('receipts/{receipt}/download-pdf', [ADMIN\ReceiptController::class, 'downloadPdf'])->name('receipts.download-pdf');
    Route::get('receipts/{receipt}/view-html', [ADMIN\ReceiptController::class, 'viewHtml'])->name('receipts.view-html');
    Route::post('receipts/{receipt}/mark-printed', [ADMIN\ReceiptController::class, 'markPrinted'])->name('receipts.mark-printed');
    Route::post('receipts/{receipt}/regenerate', [ADMIN\ReceiptController::class, 'regenerate'])->name('receipts.regenerate');
    Route::delete('receipts/{receipt}', [ADMIN\ReceiptController::class, 'destroy'])->name('receipts.destroy');
    Route::get('receipts/statistics', [ADMIN\ReceiptController::class, 'statistics'])->name('receipts.statistics');

    // Supplier Invoices
    Route::resource('supplier-invoices', ADMIN\SupplierInvoiceController::class)->except('show');
    Route::get('supplier-invoices/{supplierInvoice}', [ADMIN\SupplierInvoiceController::class, 'show'])->name('supplier-invoices.show');
    Route::post('supplier-invoices/{supplierInvoice}/approve', [ADMIN\SupplierInvoiceController::class, 'approve'])->name('supplier-invoices.approve');
    Route::post('supplier-invoices/{supplierInvoice}/reject', [ADMIN\SupplierInvoiceController::class, 'reject'])->name('supplier-invoices.reject');
    Route::post('supplier-invoices/{supplierInvoice}/cancel', [ADMIN\SupplierInvoiceController::class, 'cancel'])->name('supplier-invoices.cancel');
    Route::post('supplier-invoices/{supplierInvoice}/add-payment', [ADMIN\SupplierInvoiceController::class, 'addPayment'])->name('supplier-invoices.add-payment');
    Route::post('supplier-invoices/payments/{paymentId}/approve', [ADMIN\SupplierInvoiceController::class, 'approvePayment'])->name('supplier-invoices.approve-payment');
    Route::get('supplier-invoices/pending', [ADMIN\SupplierInvoiceController::class, 'pending'])->name('supplier-invoices.pending');
    Route::get('supplier-invoices/overdue', [ADMIN\SupplierInvoiceController::class, 'overdue'])->name('supplier-invoices.overdue');
    Route::get('supplier-invoices/unpaid', [ADMIN\SupplierInvoiceController::class, 'unpaid'])->name('supplier-invoices.unpaid');
    Route::get('supplier-invoices/statistics', [ADMIN\SupplierInvoiceController::class, 'statistics'])->name('supplier-invoices.statistics');
    Route::get('supplier-invoices/aging-report', [ADMIN\SupplierInvoiceController::class, 'agingReport'])->name('supplier-invoices.aging-report');
    Route::post('supplier-invoices/create-from-purchase', [ADMIN\SupplierInvoiceController::class, 'createFromPurchase'])->name('supplier-invoices.create-from-purchase');

    // Doctor Attention Alerts
    Route::get('doctor-attention/dashboard', [ADMIN\DoctorAttentionController::class, 'dashboard'])->name('doctor-attention.dashboard');
    Route::get('doctor-attention/needing-attention', [ADMIN\DoctorAttentionController::class, 'needingAttention'])->name('doctor-attention.needing-attention');
    Route::get('doctor-attention/critical', [ADMIN\DoctorAttentionController::class, 'critical'])->name('doctor-attention.critical');
    Route::get('doctor-attention/alerts', [ADMIN\DoctorAttentionController::class, 'alerts'])->name('doctor-attention.alerts');
    Route::post('doctor-attention/alerts/{alertId}/read', [ADMIN\DoctorAttentionController::class, 'markAsRead'])->name('doctor-attention.mark-read');
    Route::post('doctor-attention/alerts/{alertId}/action', [ADMIN\DoctorAttentionController::class, 'markAsActionTaken'])->name('doctor-attention.mark-action');
    Route::get('doctor-attention/statistics', [ADMIN\DoctorAttentionController::class, 'statistics'])->name('doctor-attention.statistics');
    Route::put('doctor-attention/settings', [ADMIN\DoctorAttentionController::class, 'updateSettings'])->name('doctor-attention.update-settings');
    Route::get('doctor-attention/settings', [ADMIN\DoctorAttentionController::class, 'getSettings'])->name('doctor-attention.get-settings');
    Route::post('doctor-attention/calculate-scores', [ADMIN\DoctorAttentionController::class, 'calculateScores'])->name('doctor-attention.calculate-scores');
    Route::post('doctor-attention/record-activity', [ADMIN\DoctorAttentionController::class, 'recordActivity'])->name('doctor-attention.record-activity');

    // Purchase Orders
    Route::resource('purchase-orders', ADMIN\PurchaseOrderController::class)->except('show');
    // Specific routes BEFORE wildcard to avoid {purchaseOrder} capturing them
    Route::get('purchase-orders/pending', [ADMIN\PurchaseOrderController::class, 'pending'])->name('purchase-orders.pending');
    Route::get('purchase-orders/overdue', [ADMIN\PurchaseOrderController::class, 'overdue'])->name('purchase-orders.overdue');
    Route::get('purchase-orders/statistics', [ADMIN\PurchaseOrderController::class, 'statistics'])->name('purchase-orders.statistics');
    Route::post('purchase-orders/{purchaseOrder}/send', [ADMIN\PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::post('purchase-orders/{purchaseOrder}/approve', [ADMIN\PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/reject', [ADMIN\PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [ADMIN\PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('purchase-orders/{purchaseOrder}/restore', [ADMIN\PurchaseOrderController::class, 'restore'])->name('purchase-orders.restore');
    Route::post('purchase-orders/{purchaseOrder}/convert', [ADMIN\PurchaseOrderController::class, 'convertToPurchase'])->name('purchase-orders.convert');
    Route::get('purchase-orders/{purchaseOrder}/pdf', [ADMIN\PurchaseOrderController::class, 'pdf'])->name('purchase-orders.pdf');
    Route::get('purchase-orders/{purchaseOrder}', [ADMIN\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');

    // GRN (Goods Received Notes)
    Route::resource('grn', ADMIN\GRNController::class)->except('show');
    Route::get('grn/{grn}', [ADMIN\GRNController::class, 'show'])->name('grn.show');
    Route::post('grn/{grn}/verify', [ADMIN\GRNController::class, 'verify'])->name('grn.verify');
    Route::post('grn/{grn}/accept', [ADMIN\GRNController::class, 'accept'])->name('grn.accept');
    Route::post('grn/{grn}/reject', [ADMIN\GRNController::class, 'reject'])->name('grn.reject');
    Route::get('grn/pending', [ADMIN\GRNController::class, 'pending'])->name('grn.pending');
    Route::get('grn/statistics', [ADMIN\GRNController::class, 'statistics'])->name('grn.statistics');

    // Advanced Supplier Management
    Route::resource('suppliers', ADMIN\SupplierController::class)->except('show');
    Route::get('suppliers/{supplier}', [ADMIN\SupplierController::class, 'show'])->name('suppliers.show');
    Route::post('suppliers/{supplier}/calculate-performance', [ADMIN\SupplierController::class, 'calculatePerformance'])->name('suppliers.calculate-performance');
    Route::get('suppliers/top-performers', [ADMIN\SupplierController::class, 'topPerformers'])->name('suppliers.top-performers');

    // Supplier Payment Tracking
    Route::resource('supplier-payments', ADMIN\SupplierPaymentController::class)->except('show');
    Route::get('supplier-payments/{supplierPayment}', [ADMIN\SupplierPaymentController::class, 'show'])->name('supplier-payments.show');
    Route::post('supplier-payments/{supplierPayment}/approve', [ADMIN\SupplierPaymentController::class, 'approve'])->name('supplier-payments.approve');
    Route::get('supplier-payments/generate-aging-report', [ADMIN\SupplierPaymentController::class, 'generateAgingReport'])->name('supplier-payments.generate-aging-report');

    // Subscription Management
    Route::resource('subscriptions', ADMIN\SubscriptionController::class)->except('show');
    Route::get('subscriptions/{subscription}', [ADMIN\SubscriptionController::class, 'show'])->name('subscriptions.show');
    Route::post('subscriptions/{subscription}/upgrade', [ADMIN\SubscriptionController::class, 'upgrade'])->name('subscriptions.upgrade');
    Route::post('subscriptions/{subscription}/downgrade', [ADMIN\SubscriptionController::class, 'downgrade'])->name('subscriptions.downgrade');
    Route::post('subscriptions/{subscription}/cancel', [ADMIN\SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('subscriptions/{subscription}/renew', [ADMIN\SubscriptionController::class, 'renew'])->name('subscriptions.renew');
    Route::post('subscriptions/{subscription}/generate-invoice', [ADMIN\SubscriptionController::class, 'generateInvoice'])->name('subscriptions.generate-invoice');
    Route::get('subscriptions/expiring', [ADMIN\SubscriptionController::class, 'expiring'])->name('subscriptions.expiring');
    Route::get('subscriptions/trial-ending', [ADMIN\SubscriptionController::class, 'trialEnding'])->name('subscriptions.trial-ending');

    // Dashboard Reports
    Route::get('dashboard-reports', [ADMIN\DashboardReportController::class, 'index'])->name('dashboard-reports.index');
    Route::get('dashboard-reports/overall', [ADMIN\DashboardReportController::class, 'overall'])->name('dashboard-reports.overall');
    Route::get('dashboard-reports/sales-by-date', [ADMIN\DashboardReportController::class, 'salesByDate'])->name('dashboard-reports.sales-by-date');
    Route::get('dashboard-reports/top-products', [ADMIN\DashboardReportController::class, 'topProducts'])->name('dashboard-reports.top-products');
    Route::get('dashboard-reports/top-customers', [ADMIN\DashboardReportController::class, 'topCustomers'])->name('dashboard-reports.top-customers');
    Route::get('dashboard-reports/profit-loss', [ADMIN\DashboardReportController::class, 'profitLoss'])->name('dashboard-reports.profit-loss');
    Route::get('dashboard-reports/warehouse', [ADMIN\DashboardReportController::class, 'warehouse'])->name('dashboard-reports.warehouse');
    Route::get('dashboard-reports/transfers', [ADMIN\DashboardReportController::class, 'transfers'])->name('dashboard-reports.transfers');
    Route::get('dashboard-reports/recalls', [ADMIN\DashboardReportController::class, 'recalls'])->name('dashboard-reports.recalls');
    Route::get('dashboard-reports/loyalty', [ADMIN\DashboardReportController::class, 'loyalty'])->name('dashboard-reports.loyalty');
    Route::get('dashboard-reports/receipts', [ADMIN\DashboardReportController::class, 'receipts'])->name('dashboard-reports.receipts');
    Route::get('dashboard-reports/comprehensive', [ADMIN\DashboardReportController::class, 'comprehensive'])->name('dashboard-reports.comprehensive');

    // Audit Logs
    Route::get('audit-logs', [ADMIN\AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('audit-logs/{auditLog}', [ADMIN\AuditLogController::class, 'show'])->name('audit-logs.show');
    Route::get('audit-logs/statistics', [ADMIN\AuditLogController::class, 'statistics'])->name('audit-logs.statistics');
    Route::get('audit-logs/model-logs', [ADMIN\AuditLogController::class, 'modelLogs'])->name('audit-logs.model-logs');
    Route::get('audit-logs/user-logs', [ADMIN\AuditLogController::class, 'userLogs'])->name('audit-logs.user-logs');
    Route::delete('audit-logs/{auditLog}', [ADMIN\AuditLogController::class, 'destroy'])->name('audit-logs.destroy');
    Route::post('audit-logs/clean-old', [ADMIN\AuditLogController::class, 'cleanOldLogs'])->name('audit-logs.clean-old');

    // Insurance Module
    Route::prefix('insurance')->name('insurance.')->group(function () {
        // Insurance Companies
        Route::resource('companies', ADMIN\InsuranceCompanyController::class)->except('show');
        Route::get('companies/{company}', [ADMIN\InsuranceCompanyController::class, 'show'])->name('companies.show');

        // Insurance Policies
        Route::resource('policies', ADMIN\InsurancePolicyController::class)->except('show');
        Route::get('policies/{policy}', [ADMIN\InsurancePolicyController::class, 'show'])->name('policies.show');
        Route::post('policies/{policy}/check-eligibility', [ADMIN\InsurancePolicyController::class, 'checkEligibility'])->name('policies.check-eligibility');

        // Insurance Claims
        Route::resource('claims', ADMIN\InsuranceClaimController::class)->except('show');
        Route::get('claims/{claim}', [ADMIN\InsuranceClaimController::class, 'show'])->name('claims.show');
        Route::post('claims/{claim}/submit', [ADMIN\InsuranceClaimController::class, 'submit'])->name('claims.submit');
        Route::post('claims/{claim}/process', [ADMIN\InsuranceClaimController::class, 'process'])->name('claims.process');
        Route::post('claims/{claim}/payment', [ADMIN\InsuranceClaimController::class, 'processPayment'])->name('claims.payment');
        Route::get('claims/statistics', [ADMIN\InsuranceClaimController::class, 'statistics'])->name('claims.statistics');
    });

    // Online Store - Customer Orders
    Route::get('customer-orders', [ADMIN\CustomerOrderController::class, 'index'])->name('customer-orders.index');
    Route::get('customer-orders/{customerOrder}', [ADMIN\CustomerOrderController::class, 'show'])->name('customer-orders.show');
    Route::put('customer-orders/{customerOrder}/status', [ADMIN\CustomerOrderController::class, 'updateStatus'])->name('customer-orders.update-status');
    Route::put('customer-orders/{customerOrder}/payment', [ADMIN\CustomerOrderController::class, 'updatePayment'])->name('customer-orders.update-payment');
    Route::get('customer-orders/export/csv', [ADMIN\CustomerOrderController::class, 'exportCsv'])->name('customer-orders.export-csv');

    // Online Store Analytics Dashboard
    Route::get('online-store', [ADMIN\OnlineStoreController::class, 'index'])->name('online-store.index');

    // Product Reviews Management
    Route::get('reviews', [ADMIN\ReviewController::class, 'index'])->name('reviews.index');
    Route::post('reviews/{review}/approve', [ADMIN\ReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('reviews/{review}/reject', [ADMIN\ReviewController::class, 'reject'])->name('reviews.reject');
    Route::delete('reviews/{review}', [ADMIN\ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Coupons Management
    Route::resource('coupons', ADMIN\CouponController::class);
    Route::post('coupons/{coupon}/toggle-status', [ADMIN\CouponController::class, 'toggleStatus'])->name('coupons.toggle-status');
    Route::post('coupons/bulk-delete', [ADMIN\CouponController::class, 'bulkDelete'])->name('coupons.bulk-delete');
    Route::post('coupons/bulk-toggle-status', [ADMIN\CouponController::class, 'bulkToggleStatus'])->name('coupons.bulk-toggle-status');
    Route::get('coupons/analytics', [ADMIN\CouponAnalyticsController::class, 'index'])->name('coupons.analytics');
    Route::get('coupons/import', [ADMIN\CouponImportController::class, 'index'])->name('coupons.import');
    Route::post('coupons/import/preview', [ADMIN\CouponImportController::class, 'preview'])->name('coupons.import.preview');
    Route::post('coupons/import/confirm', [ADMIN\CouponImportController::class, 'confirm'])->name('coupons.import.confirm');
    Route::get('coupons/import/sample', [ADMIN\CouponImportController::class, 'downloadSample'])->name('coupons.import.sample');
    Route::get('coupons/bulk-generate', [ADMIN\CouponController::class, 'bulkGenerateForm'])->name('coupons.bulk-generate');
    Route::post('coupons/bulk-generate', [ADMIN\CouponController::class, 'bulkGenerate'])->name('coupons.bulk-generate.store');
    Route::post('coupons/export-codes', [ADMIN\CouponController::class, 'exportCodes'])->name('coupons.export-codes');
    Route::post('coupons/export-csv', [ADMIN\CouponController::class, 'exportCsv'])->name('coupons.export-csv');
    Route::get('coupons/qr-codes', [ADMIN\CouponController::class, 'qrCodes'])->name('coupons.qr-codes');

    // Inventory Alerts
    Route::get('inventory-alerts', [ADMIN\InventoryAlertController::class, 'index'])->name('inventory-alerts.index');
    Route::post('inventory-alerts/{alert}/acknowledge', [ADMIN\InventoryAlertController::class, 'acknowledge'])->name('inventory-alerts.acknowledge');
    Route::post('inventory-alerts/acknowledge-all', [ADMIN\InventoryAlertController::class, 'acknowledgeAll'])->name('inventory-alerts.acknowledge-all');
    Route::post('inventory-alerts/scan', [ADMIN\InventoryAlertController::class, 'runScan'])->name('inventory-alerts.scan');
    Route::get('inventory-alerts/chart-data', [ADMIN\InventoryAlertController::class, 'chartData'])->name('inventory-alerts.chart-data');
    Route::get('inventory-alerts/bell-data', [ADMIN\InventoryAlertController::class, 'bellData'])->name('inventory-alerts.bell-data');
    Route::post('inventory-alerts/{alert}/acknowledge-alert', [ADMIN\InventoryAlertController::class, 'acknowledgeAlert'])->name('inventory-alerts.acknowledge-alert');

    // Push Notification Preferences & Device Management
    Route::get('push-notifications', [ADMIN\PushNotificationPreferencesController::class, 'index'])->name('push-notifications.index');
    Route::post('push-notifications/update-preferences', [ADMIN\PushNotificationPreferencesController::class, 'updatePreferences'])->name('push-notifications.update-preferences');
    Route::post('push-notifications/toggle-type', [ADMIN\PushNotificationPreferencesController::class, 'toggleType'])->name('push-notifications.toggle-type');
    Route::post('push-notifications/devices/{device}/deactivate', [ADMIN\PushNotificationPreferencesController::class, 'deactivateDevice'])->name('push-notifications.deactivate-device');
    Route::delete('push-notifications/devices/{device}/remove', [ADMIN\PushNotificationPreferencesController::class, 'removeDevice'])->name('push-notifications.remove-device');
    Route::post('push-notifications/deactivate-all', [ADMIN\PushNotificationPreferencesController::class, 'deactivateAllDevices'])->name('push-notifications.deactivate-all');

    // Comparison Analytics
    Route::get('comparison-analytics', [ADMIN\ComparisonAnalyticsController::class, 'index'])->name('comparison-analytics.index');

    // Sales Report
    Route::get('sales-report', [ADMIN\SalesReportController::class, 'index'])->name('sales-report.index');
    Route::get('sales-report/chart-data', [ADMIN\SalesReportController::class, 'chartData'])->name('sales-report.chart-data');
});


