<?php

use App\Http\Controllers\Admin as ADMIN;
use Illuminate\Support\Facades\Route;

Route::group(['as' => 'admin.', 'prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () {
    Route::get('/', [ADMIN\DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/get-dashboard', [ADMIN\DashboardController::class, 'getDashboardData'])->name('dashboard.data');
    Route::get('/yearly-subscriptions', [ADMIN\DashboardController::class, 'yearlySubscriptions'])->name('dashboard.subscriptions');
    Route::get('/plans-overview', [ADMIN\DashboardController::class, 'subscriptionPlan'])->name('dashboard.plans-overview');

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
    Route::get('plans/popular', [ADMIN\ZystPlanController::class, 'popularPlans'])->name('plans.popular');
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

    // Settings
    Route::resource('settings', ADMIN\SettingController::class)->only('index', 'update');
    Route::resource('system-settings', ADMIN\SystemSettingController::class)->only('index', 'store');

    // Gateway
    Route::resource('gateways', ADMIN\GatewayController::class)->only('index', 'update');
    
    // Tenant Payment Settings
    Route::resource('tenant-payment-settings', ADMIN\TenantPaymentSettingController::class);
    Route::post('tenant-payment-settings/{id}/toggle', [ADMIN\TenantPaymentSettingController::class, 'toggleStatus'])->name('tenant-payment-settings.toggle');
    Route::get('tenant-payment-settings/tenant/{tenantId}', [ADMIN\TenantPaymentSettingController::class, 'getTenantSettings'])->name('tenant-payment-settings.tenant');

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
    Route::get('purchase-orders/{purchaseOrder}', [ADMIN\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::post('purchase-orders/{purchaseOrder}/send', [ADMIN\PurchaseOrderController::class, 'send'])->name('purchase-orders.send');
    Route::post('purchase-orders/{purchaseOrder}/approve', [ADMIN\PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('purchase-orders/{purchaseOrder}/reject', [ADMIN\PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('purchase-orders/{purchaseOrder}/cancel', [ADMIN\PurchaseOrderController::class, 'cancel'])->name('purchase-orders.cancel');
    Route::post('purchase-orders/{purchaseOrder}/restore', [ADMIN\PurchaseOrderController::class, 'restore'])->name('purchase-orders.restore');
    Route::post('purchase-orders/{purchaseOrder}/convert', [ADMIN\PurchaseOrderController::class, 'convertToPurchase'])->name('purchase-orders.convert');
    Route::get('purchase-orders/{purchaseOrder}/pdf', [ADMIN\PurchaseOrderController::class, 'pdf'])->name('purchase-orders.pdf');
    Route::get('purchase-orders/pending', [ADMIN\PurchaseOrderController::class, 'pending'])->name('purchase-orders.pending');
    Route::get('purchase-orders/overdue', [ADMIN\PurchaseOrderController::class, 'overdue'])->name('purchase-orders.overdue');
    Route::get('purchase-orders/statistics', [ADMIN\PurchaseOrderController::class, 'statistics'])->name('purchase-orders.statistics');

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
});
