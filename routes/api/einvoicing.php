<?php

use App\Http\Controllers\Api;
use App\Http\Controllers\Api\EInvoicingController;
use App\Http\Controllers\Api\MarketingAutomationController;
use App\Http\Controllers\Api\TenantOnboardingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['business.context', 'throttle:20,1'])->group(function () {
    // E-Invoicing
    Route::prefix('einvoicing')->group(function () {
        Route::get('/', [EInvoicingController::class, 'index']);
        Route::post('/', [EInvoicingController::class, 'store']);
        Route::get('/{invoice}', [EInvoicingController::class, 'show']);
        Route::put('/{invoice}', [EInvoicingController::class, 'update']);
        Route::delete('/{invoice}', [EInvoicingController::class, 'destroy']);
        Route::post('/{invoice}/validate', [EInvoicingController::class, 'validateInvoice']);
        Route::post('/{invoice}/submit', [EInvoicingController::class, 'submit']);
        Route::post('/{invoice}/sync', [EInvoicingController::class, 'sync']);
        Route::post('/{invoice}/cancel', [EInvoicingController::class, 'cancel']);
        Route::get('/{invoice}/qr-code', [EInvoicingController::class, 'qrCode']);
        Route::get('/portal-status', [EInvoicingController::class, 'portalStatus']);
        Route::post('/batch-submit', [EInvoicingController::class, 'batchSubmit']);
    });

    // Marketing Automation
    Route::prefix('marketing')->group(function () {
        Route::get('/', [MarketingAutomationController::class, 'index']);
        Route::post('/', [MarketingAutomationController::class, 'store']);
        Route::get('/{campaign}', [MarketingAutomationController::class, 'show']);
        Route::put('/{campaign}', [MarketingAutomationController::class, 'update']);
        Route::delete('/{campaign}', [MarketingAutomationController::class, 'destroy']);
        Route::post('/{campaign}/send', [MarketingAutomationController::class, 'send']);
        Route::get('/{campaign}/analytics', [MarketingAutomationController::class, 'analytics']);
        Route::post('/{campaign}/schedule', [MarketingAutomationController::class, 'schedule']);
        Route::post('/{campaign}/cancel', [MarketingAutomationController::class, 'cancel']);
        Route::get('/segments', [MarketingAutomationController::class, 'segments']);
    });

    // Tenant Onboarding
    Route::prefix('onboarding')->group(function () {
        Route::get('/progress', [TenantOnboardingController::class, 'progress']);
        Route::get('/checklist', [TenantOnboardingController::class, 'checklist']);
        Route::post('/checklist/{checklistItemId}/complete', [TenantOnboardingController::class, 'completeChecklistItem']);
        Route::get('/analytics', [TenantOnboardingController::class, 'analytics']);
        Route::post('/start', [TenantOnboardingController::class, 'start']);
        Route::post('/{instanceId}/next-step', [TenantOnboardingController::class, 'nextStep']);
        Route::get('/templates', [TenantOnboardingController::class, 'templates']);
        Route::post('/templates', [TenantOnboardingController::class, 'storeTemplate']);
        Route::get('/templates/default', [TenantOnboardingController::class, 'defaultTemplate']);
        Route::get('/templates/{template}', [TenantOnboardingController::class, 'showTemplate']);
        Route::put('/templates/{template}', [TenantOnboardingController::class, 'updateTemplate']);
        Route::delete('/templates/{template}', [TenantOnboardingController::class, 'destroyTemplate']);
    });
});
