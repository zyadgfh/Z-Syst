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

        Route::apiResource('banners', Api\AcnooBannerController::class)->only('index');
        Route::apiResource('lang', Api\AcnooLanguageController::class)->only('index', 'store');
        Route::apiResource('profile', Api\AcnooProfileController::class)->only('index', 'store');
        Route::apiResource('plans', Api\AcnooSubscriptionsController::class)->only('index');
        Route::apiResource('subscribes', Api\AcnooSubscribesController::class)->only('index');
        Route::apiResource('currencies', Api\AcnooCurrencyController::class)->only('index');
        Route::apiResource('taxes', Api\AcnooTaxController::class)->except('show');

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

        Route::post('change-password', [Api\AcnooProfileController::class, 'changePassword']);

        Route::get('new-invoice', [Api\AcnooInvoiceController::class, 'newInvoice']);
        Route::get('/sign-out', [Api\Auth\AuthController::class, 'signOut']);
        Route::get('/refresh-token', [Api\Auth\AuthController::class, 'refreshToken']);
    });
});
