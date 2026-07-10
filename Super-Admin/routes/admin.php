<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin as ADMIN;

Route::group(['as' => 'admin.', 'prefix' => 'admin', 'middleware' => ['auth', 'admin']], function () {
    Route::get('/', [ADMIN\DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/get-dashboard', [ADMIN\DashboardController::class, 'getDashboardData'])->name('dashboard.data');
    Route::get('/yearly-subscriptions', [ADMIN\DashboardController::class, 'yearlySubscriptions'])->name('dashboard.subscriptions');
    Route::get('/plans-overview', [ADMIN\DashboardController::class, 'plansOverview'])->name('dashboard.plans-overview');

    // Website settings
    Route::resource('website-settings',Admin\AcnooWebSettingController::class)->only('index', 'update');

    // Features
    Route::resource('features',Admin\AcnooFeatureController::class);
    Route::post('features/status/{id}', [ADMIN\AcnooFeatureController::class,'status'])->name('features.status');
    Route::post('features/delete-all', [ADMIN\AcnooFeatureController::class, 'deleteAll'])->name('features.delete-all');

    // Interface
    Route::resource('interfaces',Admin\AcnooInterfaceController::class);
    Route::put('admin/interfaces/{interface}', [ADMIN\AcnooInterfaceController::class, 'update'])->name('interfaces.update');
    Route::post('interfaces/status/{id}', [ADMIN\AcnooInterfaceController::class,'status'])->name('interfaces.status');
    Route::post('interfaces/delete-all', [ADMIN\AcnooInterfaceController::class, 'deleteAll'])->name('interfaces.delete-all');

    // Testimonial
    Route::resource('testimonials',Admin\AcnooTestimonialController::class);
    Route::post('testimonials/delete-all', [ADMIN\AcnooTestimonialController::class, 'deleteAll'])->name('testimonials.delete-all');

    // Term And Condition Controller
    Route::resource('term-conditions', ADMIN\AcnooTermConditionController::class)->only('index', 'store');

    // Message
    Route::resource('messages', Admin\AcnooMessageController::class)->only('index', 'destroy');
    Route::post('messages/filter', [ADMIN\AcnooMessageController::class, 'acnooFilter'])->name('messages.filter');
    Route::post('messages/delete-all', [ADMIN\AcnooMessageController::class, 'deleteAll'])->name('messages.delete-all');
    Route::post('messages/filter', [Admin\AcnooMessageController::class, 'acnooFilter'])->name('messages.filter');

    // Privacy And Policy Controller
    Route::resource('privacy-policy', ADMIN\AcnooPrivacyPloicyController::class)->only('index', 'store');

    // Blog Controller
    Route::resource('blogs', Admin\AcnooBlogController::class);
    Route::post('blogs/status/{id}', [ADMIN\AcnooBlogController::class,'status'])->name('blogs.status');
    Route::post('blogs/delete-all', [ADMIN\AcnooBlogController::class, 'deleteAll'])->name('blogs.delete-all');
    Route::get('blogs/comments/{id}', [ADMIN\AcnooBlogController::class, 'filterComment'])->name('blogs.filter.comment');

    //Comment Controller
    Route::resource('comments', Admin\AcnooCommentController::class);
    Route::post('comments/delete-all', [ADMIN\AcnooCommentController::class, 'deleteAll'])->name('comments.delete-all');

    Route::resource('users', ADMIN\UserController::class);
    Route::post('users/status/{id}', [ADMIN\UserController::class,'status'])->name('users.status');
    Route::post('users/delete-all', [ADMIN\UserController::class,'deleteAll'])->name('users.delete-all');

    Route::resource('banners', ADMIN\AcnooBannerController::class)->except('show', 'edit', 'create');
    Route::post('banners/status/{id}', [ADMIN\AcnooBannerController::class,'status'])->name('banners.status');
    Route::post('banners/delete-all', [ADMIN\AcnooBannerController::class,'deleteAll'])->name('banners.delete-all');

    //Subscription Plans
    Route::resource('plans', ADMIN\AcnooPlanController::class)->except('show');
    Route::post('plans/status/{id}', [ADMIN\AcnooPlanController::class,'status'])->name('plans.status');
    Route::post('plans/delete-all', [ADMIN\AcnooPlanController::class, 'deleteAll'])->name('plans.delete-all');

    // Business
    Route::resource('business',ADMIN\AcnooBusinessController::class);
    Route::put('business/upgrade-plan/{id}', [ADMIN\AcnooBusinessController::class, 'upgradePlan'])->name('business.upgrade.plan');
    Route::post('business/status/{id}',[ADMIN\AcnooBusinessController::class,'status'])->name('business.status');
    Route::post('business/delete-all', [ADMIN\AcnooBusinessController::class,'deleteAll'])->name('business.delete-all');

    // Business Categories
    Route::resource('business-categories',ADMIN\AcnooBusinessCategoryController::class)->except('show');
    Route::post('business-categories/status/{id}',[ADMIN\AcnooBusinessCategoryController::class,'status'])->name('business-categories.status');
    Route::post('business-categories/delete-all', [ADMIN\AcnooBusinessCategoryController::class,'deleteAll'])->name('business-categories.delete-all');

    Route::resource('profiles', ADMIN\ProfileController::class)->only('index', 'update');

    Route::resource('subscription-reports', ADMIN\SubscriptionReport::class)->only('index');

    Route::resource('subscription-orders', ADMIN\AcnooSubscriptionController::class)->only('index');
    Route::post('subscription-orders/reject/{id}',[ADMIN\AcnooSubscriptionController::class,'reject'])->name('subscription-orders.reject');
    Route::post('subscription-orders/paid/{id}',[ADMIN\AcnooSubscriptionController::class,'paid'])->name('subscription-orders.paid');
    Route::get('subscription-orders/get-invoice/{id}', [ADMIN\AcnooSubscriptionController::class, 'getInvoice'])->name('subscription-orders.invoice');

    // Affiliates
    Route::resource('affiliates',ADMIN\AcnooAffiliateController::class);
    Route::post('affiliates/filter', [ADMIN\AcnooAffiliateController::class, 'acnooFilter'])->name('affiliates.filter');
    Route::post('affiliates/status/{id}',[ADMIN\AcnooAffiliateController::class,'status'])->name('affiliates.status');
    Route::post('affiliates/delete-all', [ADMIN\AcnooAffiliateController::class,'deleteAll'])->name('affiliates.delete-all');

    // Affiliates Withdraw Request
    Route::resource('affiliate-withdrawals',ADMIN\AcnooWithdrawRequestController::class)->only('index');
    Route::post('affiliate-withdrawals/reject/{id}',[ADMIN\AcnooWithdrawRequestController::class,'reject'])->name('affiliate-withdrawals.reject');
    Route::post('affiliate-withdrawals/paid/{id}',[ADMIN\AcnooWithdrawRequestController::class,'paid'])->name('affiliate-withdrawals.paid');
    Route::post('affiliate-withdrawals/filter', [ADMIN\AcnooWithdrawRequestController::class, 'acnooFilter'])->name('affiliate-withdrawals.filter');
    Route::post('affiliate-withdrawals/status/{id}',[ADMIN\AcnooWithdrawRequestController::class,'status'])->name('affiliate-withdrawals.status');
    Route::post('affiliate-withdrawals/delete-all', [ADMIN\AcnooWithdrawRequestController::class,'deleteAll'])->name('affiliate-withdrawals.delete-all');

    // Affiliates Report
    Route::resource('affiliate-reports',ADMIN\AcnooAffiliateReportController::class)->only('index');
    Route::post('affiliate-reports/filter', [ADMIN\AcnooAffiliateReportController::class, 'acnooFilter'])->name('affiliate-reports.filter');

    // Roles & Permissions
    Route::resource('roles', ADMIN\RoleController::class)->except('show');
    Route::resource('permissions', ADMIN\PermissionController::class)->only('index', 'store');

    // Settings
    Route::resource('addons', ADMIN\AddonController::class)->only('index', 'store', 'show');
    Route::resource('settings', ADMIN\SettingController::class)->only('index', 'update');
    Route::resource('system-settings', ADMIN\SystemSettingController::class)->only('index', 'store');

    // Gateway
    Route::resource('gateways', ADMIN\GatewayController::class)->only('index', 'update');

    Route::resource('manage-settings', ADMIN\AcnooSettingsManagerController::class)->only('index', 'store');
    Route::post('manage-setting/domain', [ADMIN\AcnooSettingsManagerController::class,'domain'])->name('domain.setting');

    Route::resource('currencies', ADMIN\AcnooCurrencyController::class)->except('show');
    Route::post('currencies/filter', [ADMIN\AcnooCurrencyController::class, 'acnooFilter'])->name('currencies.filter');
    Route::match(['get', 'post'], 'currencies/default/{id}', [ADMIN\AcnooCurrencyController::class, 'default'])->name('currencies.default');
    Route::post('currencies/delete-all', [ADMIN\AcnooCurrencyController::class,'deleteAll'])->name('currencies.delete-all');

    // Notifications manager
    Route::prefix('notifications')->controller(ADMIN\NotificationController::class)->name('notifications.')->group(function () {
        Route::get('/', 'mtIndex')->name('index');
        Route::get('/{id}', 'mtView')->name('mtView');
        Route::get('view/all/', 'mtReadAll')->name('mtReadAll');
    });
});
