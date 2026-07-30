<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin as ADMIN;

Route::group(['as' => 'admin.', 'prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () {
    Route::get('/', [ADMIN\DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/get-dashboard', [ADMIN\DashboardController::class, 'getDashboardData'])->name('dashboard.data');
    Route::get('/yearly-subscriptions', [ADMIN\DashboardController::class, 'yearlySubscriptions'])->name('dashboard.subscriptions');
    Route::get('/plans-overview', [ADMIN\DashboardController::class, 'subscriptionPlan'])->name('dashboard.plans-overview');

    Route::resource('users', ADMIN\UserController::class);
    Route::post('users/filter', [ADMIN\UserController::class, 'zsystFilter'])->name('users.filter');
    Route::post('users/status/{id}', [ADMIN\UserController::class,'status'])->name('users.status');
    Route::post('users/delete-all', [ADMIN\UserController::class,'deleteAll'])->name('users.delete-all');
    Route::get('users-excel', [ADMIN\UserController::class, 'exportExcel'])->name('users.excel');
    Route::get('users-csv', [ADMIN\UserController::class, 'exportCsv'])->name('users.csv');


    Route::resource('banners', ADMIN\ZSystBannerController::class)->except('show', 'edit', 'create');
    Route::post('banners/filter', [ADMIN\ZSystBannerController::class, 'zsystFilter'])->name('banners.filter');
    Route::post('banners/status/{id}', [ADMIN\ZSystBannerController::class,'status'])->name('banners.status');
    Route::post('banners/delete-all', [ADMIN\ZSystBannerController::class,'deleteAll'])->name('banners.delete-all');
    Route::get('banner/excel', [ADMIN\ZSystBannerController::class, 'exportExcel'])->name('banners.excel');
    Route::get('banner/csv', [ADMIN\ZSystBannerController::class, 'exportCsv'])->name('banners.csv');

    //Subscription Plans
    Route::resource('plans', ADMIN\ZSystPlanController::class)->except('show');
    Route::post('plans/filter', [ADMIN\ZSystPlanController::class, 'zsystFilter'])->name('plans.filter');
    Route::post('plans/status/{id}', [ADMIN\ZSystPlanController::class,'status'])->name('plans.status');
    Route::post('plans/delete-all', [ADMIN\ZSystPlanController::class, 'deleteAll'])->name('plans.delete-all');
    Route::get('plans-excel', [ADMIN\ZSystPlanController::class, 'exportExcel'])->name('plans.excel');
    Route::get('plans-csv', [ADMIN\ZSystPlanController::class, 'exportCsv'])->name('plans.csv');

    // Business
    Route::resource('business',ADMIN\ZSystBusinessController::class);
    Route::put('business/upgrade-plan/{id}', [ADMIN\ZSystBusinessController::class, 'upgradePlan'])->name('business.upgrade.plan');
    Route::post('business/filter', [ADMIN\ZSystBusinessController::class, 'zsystFilter'])->name('business.filter');
    Route::post('business/status/{id}',[ADMIN\ZSystBusinessController::class,'status'])->name('business.status');
    Route::post('business/delete-all', [ADMIN\ZSystBusinessController::class,'deleteAll'])->name('business.delete-all');
    Route::get('business-excel', [ADMIN\ZSystBusinessController::class, 'exportExcel'])->name('business.excel');
    Route::get('business-csv', [ADMIN\ZSystBusinessController::class, 'exportCsv'])->name('business.csv');

    // Business Categories
    Route::resource('business-categories',ADMIN\ZSystBusinessCategoryController::class)->except('show');
    Route::post('business-category/filter', [ADMIN\ZSystBusinessCategoryController::class, 'zsystFilter'])->name('business-categories.filter');
    Route::post('business-categories/status/{id}',[ADMIN\ZSystBusinessCategoryController::class,'status'])->name('business-categories.status');
    Route::post('business-categories/delete-all', [ADMIN\ZSystBusinessCategoryController::class,'deleteAll'])->name('business-categories.delete-all');
    Route::get('business-categories-excel', [ADMIN\ZSystBusinessCategoryController::class, 'exportExcel'])->name('business-categories.excel');
    Route::get('business-categories-csv', [ADMIN\ZSystBusinessCategoryController::class, 'exportCsv'])->name('business-categories.csv');


    Route::resource('profiles', ADMIN\ProfileController::class)->only('index', 'update');

    Route::resource('subscription-reports', ADMIN\SubscriptionReport::class)->only('index');
    Route::post('subscription-reports/filter', [ADMIN\SubscriptionReport::class, 'zsystFilter'])->name('subscription-reports.filter');
    Route::post('subscription-reports/reject/{id}',[ADMIN\SubscriptionReport::class,'reject'])->name('subscription-reports.reject');
    Route::post('subscription-reports/paid/{id}',[ADMIN\SubscriptionReport::class,'paid'])->name('subscription-reports.paid');
    Route::get('subscription-report/get-invoice/{id}', [ADMIN\SubscriptionReport::class, 'getInvoice'])->name('subscription-reports.invoice');
    Route::get('subscription-reports-excel', [ADMIN\SubscriptionReport::class, 'exportExcel'])->name('subscription-reports.excel');
    Route::get('subscription-reports-csv', [ADMIN\SubscriptionReport::class, 'exportCsv'])->name('subscription-reports.csv');

    Route::resource('manual-payments', ADMIN\ZSystManualPaymentReportController::class)->only('index');
    Route::post('manual-payments/filter', [ADMIN\ZSystManualPaymentReportController::class, 'zsystFilter'])->name('manual-payments.filter');
    Route::post('manual-payments/reject/{id}',[ADMIN\ZSystManualPaymentReportController::class,'reject'])->name('manual-payments.reject');
    Route::post('manual-payments/paid/{id}',[ADMIN\ZSystManualPaymentReportController::class,'paid'])->name('manual-payments.paid');
    Route::get('manual-payments/get-invoice/{id}', [ADMIN\ZSystManualPaymentReportController::class, 'getInvoice'])->name('manual-payments.invoice');
    Route::get('manual-payments-excel', [ADMIN\ZSystManualPaymentReportController::class, 'exportExcel'])->name('manual-payments.excel');
    Route::get('manual-payments-csv', [ADMIN\ZSystManualPaymentReportController::class, 'exportCsv'])->name('manual-payments.csv');

    // Expired Business
    Route::resource('expired-business',ADMIN\ZSystExpireBusinessReportController::class)->only('index');
    Route::post('expired-business/filter', [ADMIN\ZSystExpireBusinessReportController::class, 'zsystFilter'])->name('expired-business.filter');
    Route::get('expired-business-excel', [ADMIN\ZSystExpireBusinessReportController::class, 'exportExcel'])->name('expired-business.excel');
    Route::get('expired-business-csv', [ADMIN\ZSystExpireBusinessReportController::class, 'exportCsv'])->name('expired-business.csv');

    // Active Business
    Route::resource('active-stores',ADMIN\ZSystActiveBusinessReportController::class)->only('index');
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

    Route::resource('currencies', ADMIN\ZSystCurrencyController::class)->except('show');
    Route::post('currencies/filter', [ADMIN\ZSystCurrencyController::class, 'zsystFilter'])->name('currencies.filter');
    Route::match(['get', 'post'], 'currencies/default/{id}', [ADMIN\ZSystCurrencyController::class, 'default'])->name('currencies.default');
    Route::post('currencies/delete-all', [ADMIN\ZSystCurrencyController::class,'deleteAll'])->name('currencies.delete-all');
    Route::get('currencies-excel', [ADMIN\ZSystCurrencyController::class, 'exportExcel'])->name('currencies.excel');
    Route::get('currencies-csv', [ADMIN\ZSystCurrencyController::class, 'exportCsv'])->name('currencies.csv');


    // Prescriptions
    Route::resource('prescriptions', ADMIN\ZSystPrescriptionController::class)->except('show', 'edit', 'create');
    Route::post('prescriptions/filter', [ADMIN\ZSystPrescriptionController::class, 'zsystFilter'])->name('prescriptions.filter');
    Route::post('prescriptions/status/{id}', [ADMIN\ZSystPrescriptionController::class,'status'])->name('prescriptions.status');
    Route::post('prescriptions/delete-all', [ADMIN\ZSystPrescriptionController::class,'deleteAll'])->name('prescriptions.delete-all');
    Route::post('prescriptions/link-to-sale/{id}', [ADMIN\ZSystPrescriptionController::class,'linkToSale'])->name('prescriptions.link-to-sale');

    // Notifications manager
    Route::prefix('notifications')->controller(ADMIN\NotificationController::class)->name('notifications.')->group(function () {
        Route::get('/', 'mtIndex')->name('index');
        Route::get('/{id}', 'mtView')->name('mtView');
        Route::get('view/all/', 'mtReadAll')->name('mtReadAll');
    });
});
