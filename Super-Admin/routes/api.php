<?php

use App\Http\Controllers\Api as Api;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/module-check', [Api\Auth\AuthController::class, 'moduleCheck']);
    Route::post('/sign-in', [Api\Auth\AuthController::class, 'login']);
    Route::post('/submit-otp', [Api\Auth\AuthController::class, 'submitOtp']);
    Route::post('/sign-up', [Api\Auth\AuthController::class, 'signUp']);
    Route::post('/resend-otp', [Api\Auth\AuthController::class, 'resendOtp']);
    Route::get('/otp-settings', [Api\Auth\AuthController::class, 'otpSettings']);

    Route::post('/send-reset-code', [Api\Auth\AcnooForgotPasswordController::class, 'sendResetCode']);
    Route::post('/verify-reset-code', [Api\Auth\AcnooForgotPasswordController::class, 'verifyResetCode']);
    Route::post('/password-reset', [Api\Auth\AcnooForgotPasswordController::class, 'resetPassword']);
    Route::apiResource('default-lang', Api\DefaultLangController::class)->only('index');

    Route::group(['middleware' => ['auth:sanctum']], function () {
        Route::get('update-expire-date', [Api\BusinessController::class, 'updateExpireDate']);

        Route::get('summary', [Api\StatisticsController::class, 'summary']);
        Route::get('dashboard', [Api\StatisticsController::class, 'dashboard']);

        Route::apiResource('parties', Api\PartyController::class);
        Route::apiResource('users', Api\AcnooUserController::class)->except('show');
        Route::apiResource('units', Api\UnitController::class)->except('show');
        Route::apiResource('brands', Api\AcnooBrandController::class)->except('show');
        Route::apiResource('categories', Api\AcnooCategoryController::class)->except('show');
        Route::apiResource('products', Api\AcnooProductController::class);
        Route::apiResource('stocks', Api\StockController::class)->except('index', 'show');
        Route::apiResource('business', Api\BusinessController::class)->only('index', 'store', 'update');
        Route::apiResource('business-categories', Api\BusinessCategoryController::class)->only('index');
        Route::apiResource('purchase', Api\PurchaseController::class);
        Route::apiResource('sales', Api\AcnooSaleController::class);
        Route::get('reports/loss-profit', [Api\AcnooReportController::class,'lossProfit']);
        Route::get('reports/cashflow', [Api\AcnooReportController::class,'cashFlow']);
        Route::get('reports/balance-sheet', [Api\AcnooReportController::class,'balanceSheetReport']);
        Route::get('reports/subscription', [Api\AcnooReportController::class,'subscriptionReport']);
        Route::get('reports/tax', [Api\AcnooReportController::class,'taxReport']);
        Route::get('reports/bill-wise-profit', [Api\AcnooReportController::class,'billWiseProfitReport']);
        Route::get('reports/product-sale-history', [Api\AcnooReportController::class,'productSaleHistory']);
        Route::get('reports/product-sale-history/{id}', [Api\AcnooReportController::class,'productSaleHistoryDetails']);
        Route::get('reports/product-purchase-history', [Api\AcnooReportController::class,'productPurchaseHistory']);
        Route::get('reports/product-purchase-history/{id}', [Api\AcnooReportController::class,'productPurchaseHistoryDetails']);
        Route::apiResource('sales-return', Api\SaleReturnController::class)->only('index', 'store', 'show');
        Route::apiResource('purchases-return', Api\PurchaseReturnController::class)->only('index', 'store', 'show');
        Route::apiResource('invoices', Api\AcnooInvoiceController::class)->only('index');
        Route::apiResource('dues', Api\AcnooDueController::class)->only('index', 'store');
        Route::get('invoice-wise-dues', [Api\AcnooDueController::class, 'invoiceWiseDue']);
        Route::post('collect-invoice-due', [Api\AcnooDueController::class, 'collectInvoiceDue']);
        Route::apiResource('expense-categories', Api\ExpenseCategoryController::class)->except('show');
        Route::apiResource('expenses', Api\AcnooExpenseController::class)->only('index', 'store');
        Route::apiResource('income-categories', Api\AcnooIncomeCategoryController::class)->except('show');
        Route::apiResource('incomes', Api\AcnooIncomeController::class)->only('index', 'store');

        Route::apiResource('banners', Api\AcnooBannerController::class)->only('index');
        Route::apiResource('lang', Api\AcnooLanguageController::class)->only('index', 'store');
        Route::apiResource('profile', Api\AcnooProfileController::class)->only('index', 'store');
        Route::apiResource('plans', Api\AcnooSubscriptionsController::class)->only('index');
        Route::apiResource('subscribes', Api\AcnooSubscribesController::class)->only('index');
        Route::apiResource('currencies', Api\AcnooCurrencyController::class)->only('index', 'show');
        Route::apiResource('vats', Api\AcnooVatController::class)->except('show');
        Route::apiResource('payment-types', Api\PaymentTypeController::class)->except('show');
        Route::apiResource('product-models', Api\ProducModelController::class)->except('show');
        Route::apiResource('variations', Api\VariationController::class)->except('show');
        Route::apiResource('racks', Api\RackController::class)->except('show');
        Route::apiResource('shelfs', Api\ShelfController::class)->except('show');
        Route::apiResource('banks', Api\BankController::class);
        Route::apiResource('bank-transactions', Api\BankTransactionController::class);
        Route::apiResource('cashes', Api\CashController::class);
        Route::apiResource('cheques', Api\ChequeController::class)->except('update');
        Route::post('cheque-reopen/{id}', [Api\ChequeController::class, 'reopen']);

        Route::apiResource('product-settings', Api\ProductSettingsController::class)->only('index', 'store');
        Route::get('product/generate-code', [Api\AcnooProductController::class, 'generateCode']);

        Route::post('bulk-uploads', [Api\BulkUploadControler::class, 'store']);
        Route::post('change-password', [Api\AcnooProfileController::class, 'changePassword'])->name('api.change-password');

        Route::get('new-invoice', [Api\AcnooInvoiceController::class, 'newInvoice']);

        Route::get('invoice-settings', [Api\BusinessInvoiceSettingController::class, 'index']);
        Route::post('/invoice-settings/update', [Api\BusinessInvoiceSettingController::class, 'updateInvoice'])->name('invoice.update');
        Route::post('/business-delete', [Api\BusinessController::class, 'deleteBusiness']);
        Route::get('party-ledger/{party_id}', [Api\PartyController::class, 'partyLedger']);
        Route::get('money-receipt/{id}', [Api\TransactionController::class, 'moneyReceipt']);

        Route::apiResource('currency-settings', Api\BusinessCurrencySettingController::class)->only('index', 'store');
        Route::apiResource('transactions', Api\TransactionController::class)->only('index');

        Route::get('/sign-out', [Api\Auth\AuthController::class, 'signOut']);
        Route::get('/refresh-token', [Api\Auth\AuthController::class, 'refreshToken']);
    });
});
