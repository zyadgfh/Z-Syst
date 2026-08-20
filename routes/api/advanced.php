<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

Route::middleware(['business.context', 'throttle:20,1'])->group(function () {
    // Doctor Attention
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

    // Expiry Alerts
    Route::get('expiry-alerts/stats', [Api\ExpiryAlertController::class, 'stats']);
    Route::get('expiry-alerts', [Api\ExpiryAlertController::class, 'index']);

    // FEFO System
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

    // AI Sales Prediction
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

    // Inventory Turnover
    Route::prefix('inventory-turnover')->group(function () {
        Route::get('summary', [Api\InventoryTurnoverController::class, 'summary']);
        Route::get('report', [Api\InventoryTurnoverController::class, 'report']);
        Route::get('products', [Api\InventoryTurnoverController::class, 'products']);
        Route::get('product/{product}', [Api\InventoryTurnoverController::class, 'product']);
        Route::get('slow-moving', [Api\InventoryTurnoverController::class, 'slowMoving']);
        Route::get('abc-analysis', [Api\InventoryTurnoverController::class, 'abcAnalysis']);
        Route::get('trends', [Api\InventoryTurnoverController::class, 'trends']);
    });

    // Stock Audit
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

    // Financial Audit
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

    // Insurance
    Route::prefix('insurance')->group(function () {
        Route::get('summary', [Api\InsuranceController::class, 'summary']);
        Route::get('companies', [Api\InsuranceController::class, 'companiesIndex']);
        Route::post('companies', [Api\InsuranceController::class, 'companiesStore']);
        Route::get('companies/{company}', [Api\InsuranceController::class, 'companiesShow']);
        Route::put('companies/{company}', [Api\InsuranceController::class, 'companiesUpdate']);
        Route::delete('companies/{company}', [Api\InsuranceController::class, 'companiesDestroy']);
        Route::get('policies', [Api\InsuranceController::class, 'policiesIndex']);
        Route::post('policies', [Api\InsuranceController::class, 'policiesStore']);
        Route::get('policies/{policy}', [Api\InsuranceController::class, 'policiesShow']);
        Route::put('policies/{policy}', [Api\InsuranceController::class, 'policiesUpdate']);
        Route::delete('policies/{policy}', [Api\InsuranceController::class, 'policiesDestroy']);
        Route::get('claims', [Api\InsuranceController::class, 'claimsIndex']);
        Route::post('claims', [Api\InsuranceController::class, 'claimsStore']);
        Route::get('claims/{claim}', [Api\InsuranceController::class, 'claimsShow']);
        Route::post('claims/{claim}/submit', [Api\InsuranceController::class, 'claimsSubmit']);
        Route::post('claims/{claim}/approve', [Api\InsuranceController::class, 'claimsApprove']);
        Route::post('claims/{claim}/reject', [Api\InsuranceController::class, 'claimsReject']);
        Route::post('claims/{claim}/pay', [Api\InsuranceController::class, 'claimsPay']);
        Route::get('coverages', [Api\InsuranceController::class, 'coveragesIndex']);
        Route::post('coverages', [Api\InsuranceController::class, 'coveragesStore']);
        Route::put('coverages/{coverage}', [Api\InsuranceController::class, 'coveragesUpdate']);
        Route::delete('coverages/{coverage}', [Api\InsuranceController::class, 'coveragesDestroy']);
    });

    // Warehouse
    Route::prefix('warehouses')->group(function () {
        Route::get('/', [Api\WarehouseController::class, 'index']);
        Route::post('/', [Api\WarehouseController::class, 'store']);
        Route::get('/{warehouse}', [Api\WarehouseController::class, 'show']);
        Route::put('/{warehouse}', [Api\WarehouseController::class, 'update']);
        Route::delete('/{warehouse}', [Api\WarehouseController::class, 'destroy']);
        Route::get('/{warehouse}/stock', [Api\WarehouseController::class, 'stock']);
    });

    // Traceability & Recall
    Route::prefix('traceability')->group(function () {
        Route::get('batch-lots', [Api\TraceabilityController::class, 'batchLots']);
        Route::get('expiring-batches', [Api\TraceabilityController::class, 'expiringBatches']);
        Route::get('expired-batches', [Api\TraceabilityController::class, 'expiredBatches']);
        Route::get('recalls', [Api\TraceabilityController::class, 'recalls']);
        Route::post('recalls', [Api\TraceabilityController::class, 'initiateRecall']);
        Route::post('recalls/{recall}/resolve', [Api\TraceabilityController::class, 'resolveRecall']);
        Route::get('traceability', [Api\TraceabilityController::class, 'traceability']);
    });

    // Loyalty & CRM
    Route::prefix('loyalty')->group(function () {
        Route::get('/', [Api\LoyaltyController::class, 'index']);
        Route::post('/', [Api\LoyaltyController::class, 'store']);
        Route::put('/{program}', [Api\LoyaltyController::class, 'update']);
        Route::delete('/{program}', [Api\LoyaltyController::class, 'destroy']);
        Route::get('balance/{partyId?}', [Api\LoyaltyController::class, 'partyBalance']);
        Route::get('history/{partyId?}', [Api\LoyaltyController::class, 'partyHistory']);
        Route::get('interactions', [Api\LoyaltyController::class, 'interactions']);
    });

    // Receipts
    Route::prefix('receipts')->group(function () {
        Route::get('settings', [Api\ReceiptController::class, 'settings']);
        Route::put('settings', [Api\ReceiptController::class, 'updateSettings']);
        Route::post('sales/{sale}', [Api\ReceiptController::class, 'generateForSale']);
        Route::post('purchases/{purchase}', [Api\ReceiptController::class, 'generateForPurchase']);
        Route::get('/{receipt}', [Api\ReceiptController::class, 'show']);
        Route::get('/{receipt}/download', [Api\ReceiptController::class, 'download']);
    });
});
