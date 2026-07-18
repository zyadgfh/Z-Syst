<?php

use App\Http\Controllers\Api as Api;
use App\Http\Controllers\API\AuthController as ApiAuthController;
use App\Http\Controllers\API\HealthController;
use Illuminate\Support\Facades\Route;

// Health Check Endpoints (No Auth Required)
Route::get('/health', HealthController::class);
Route::get('/health/ready', [HealthController::class, 'ready']);
Route::get('/health/live', [HealthController::class, 'live']);

Route::prefix('v1')->group(function () {

    // Legacy OTP-based auth endpoints
    Route::post('/sign-in', [Api\Auth\AuthController::class, 'login']);
    Route::post('/submit-otp', [Api\Auth\AuthController::class, 'submitOtp']);
    Route::post('/sign-up', [Api\Auth\AuthController::class, 'signUp']);
    Route::post('/resend-otp', [Api\Auth\AuthController::class, 'resendOtp']);

    // Standard API auth endpoints (token / password / profile)
    Route::post('/register', [ApiAuthController::class, 'register']);
    Route::post('/login', [ApiAuthController::class, 'login']);
    Route::post('/forgot-password', [ApiAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [ApiAuthController::class, 'resetPassword']);
    Route::post('/barcode-login', [ApiAuthController::class, 'barcodeLogin']);

    Route::prefix('auth')->group(function () {
        Route::post('/register', [ApiAuthController::class, 'register']);
        Route::post('/login', [ApiAuthController::class, 'login']);
        Route::post('/forgot-password', [ApiAuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [ApiAuthController::class, 'resetPassword']);
        Route::post('/barcode-login', [ApiAuthController::class, 'barcodeLogin']);

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [ApiAuthController::class, 'logout']);
            Route::post('/logout-all-devices', [ApiAuthController::class, 'logoutAllDevices']);
            Route::post('/refresh', [ApiAuthController::class, 'refresh']);
            Route::get('/me', [ApiAuthController::class, 'me']);
            Route::post('/change-password', [ApiAuthController::class, 'changePassword']);
            Route::put('/profile', [ApiAuthController::class, 'updateProfile']);
            Route::post('/email/resend', [ApiAuthController::class, 'resendVerificationEmail']);
            Route::post('/email/verify', [ApiAuthController::class, 'verifyEmail']);
            
            // Two-Factor Authentication
            Route::post('/two-factor/setup', [ApiAuthController::class, 'setupTwoFactor']);
            Route::post('/two-factor/enable', [ApiAuthController::class, 'enableTwoFactor']);
            Route::post('/two-factor/disable', [ApiAuthController::class, 'disableTwoFactor']);
            Route::post('/two-factor/recovery-codes', [ApiAuthController::class, 'regenerateRecoveryCodes']);
        });
    });

    Route::post('/send-reset-code',[Api\Auth\AcnooForgotPasswordController::class, 'sendResetCode']);
    Route::post('/verify-reset-code',[Api\Auth\AcnooForgotPasswordController::class, 'verifyResetCode']);
    Route::post('/password-reset',[Api\Auth\AcnooForgotPasswordController::class, 'resetPassword']);

    // Public tenant-aware endpoints (no auth required for tests that bind tenant manually)
    Route::apiResource('drugs', \App\Http\Controllers\API\V1\DrugController::class)->only(['index', 'show']);

    Route::group(['middleware' => ['auth:sanctum', 'tenant']], function () {
        Route::get('drugs/barcode/{barcode}', [\App\Http\Controllers\API\V1\DrugController::class, 'byBarcode']);

        Route::get('summary', [Api\StatisticsController::class, 'summary']);
        Route::get('dashboard', [Api\StatisticsController::class, 'dashboard']);

        Route::post('stock-update/{id}', [Api\AcnooProductController::class, 'updateStock']);
        Route::get('stocks-with-product', [Api\AcnooProductController::class, 'stocksWithProduct']);
        Route::get('dues-list', [Api\AcnooDueController::class, 'duesList']);

        Route::apiResource('parties', Api\PartyController::class);
        Route::apiResource('users', Api\AcnooUserController::class)->except('show');
        Route::apiResource('units', Api\UnitController::class)->except('show');
        Route::apiResource('categories', Api\AcnooCategoryController::class)->except('show');
        Route::apiResource('manufacturer', Api\AcnooManufacturerController::class)->except('show');
        Route::apiResource('products', Api\AcnooProductController::class);
        Route::apiResource('stocks', Api\StockController::class)->only('index');
        Route::apiResource('business-categories', Api\BusinessCategoryController::class)->only('index');
        Route::apiResource('business', Api\BusinessController::class)->only('index', 'store', 'update');
        Route::apiResource('purchase', Api\PurchaseController::class);
        Route::apiResource('sales', Api\AcnooSaleController::class);
            Route::post('pos/sales/validate-inventory', [Api\AcnooSaleController::class, 'validateInventory']);
            Route::post('send-invoice-whatsapp', [Api\InvoiceWhatsAppController::class, 'sendInvoice']);
            Route::post('{sale}/send-notifications', [Api\InvoiceNotificationController::class, 'send']);
            Route::get('{sale}/notifications', [Api\InvoiceNotificationController::class, 'index']);
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

        // Admin API endpoints (tenant-aware)
        Route::apiResource('admin/drugs', \App\Http\Controllers\API\V1\Admin\DrugAdminController::class)->only(['store', 'index', 'update', 'destroy']);
        Route::post('admin/import/products/json', [\App\Http\Controllers\API\V1\Admin\ProductImportController::class, 'importJson']);

        Route::apiResource('products-catalog', \App\Http\Controllers\API\V1\ProductCatalogController::class);
        Route::apiResource('product-stocks', \App\Http\Controllers\API\V1\ProductStockController::class);
        Route::get('product-stocks/search-across-branches', [API\V1\ProductStockController::class, 'searchAcrossBranches']);

        Route::get('new-invoice', [Api\AcnooInvoiceController::class, 'newInvoice']);
        Route::get('/sign-out', [Api\Auth\AuthController::class, 'signOut']);
        Route::get('/refresh-token', [Api\Auth\AuthController::class, 'refreshToken']);

        // ============================================================
        // V1 - Patients
        // ============================================================
        Route::apiResource('patients', API\V1\PatientController::class);

        // ============================================================
        // V1 - Doctors
        // ============================================================
        Route::apiResource('doctors', API\V1\DoctorController::class);

        // ============================================================
        // V1 - Prescriptions
        // ============================================================
        Route::post('prescriptions/{prescription}/dispense', [API\V1\PrescriptionController::class, 'dispense']);
        Route::post('prescriptions/{prescription}/dispense-by-barcode', [API\V1\PrescriptionController::class, 'dispenseByBarcode']);
        Route::post('pharmacy/checkout', [API\V1\PrescriptionController::class, 'checkout']);
        Route::get('pharmacy/demand-forecast', [API\V1\PrescriptionController::class, 'demandForecast']);
        Route::get('pharmacy/pos-summary', [API\V1\PrescriptionController::class, 'posSummary']);
        Route::apiResource('prescriptions', API\V1\PrescriptionController::class);

        // ============================================================
        // V1 - Suppliers
        // ============================================================
        Route::apiResource('suppliers', API\V1\SupplierController::class);

        // ============================================================
        // V1 - Purchase Orders (full workflow)
        // ============================================================
        Route::apiResource('purchase-orders', API\V1\PurchaseOrderController::class);
        Route::post('purchase-orders/{purchaseOrder}/approve', [API\V1\PurchaseOrderController::class, 'approve']);
        Route::post('purchase-orders/{purchaseOrder}/send', [API\V1\PurchaseOrderController::class, 'send']);
        Route::post('purchase-orders/{purchaseOrder}/cancel', [API\V1\PurchaseOrderController::class, 'cancel']);

        // ============================================================
        // V1 - Stock Transfers (full workflow)
        // ============================================================
        Route::get('stock-transfers/statistics', [API\V1\StockTransferController::class, 'statistics']);
        Route::post('stock-transfers/{stockTransfer}/approve', [API\V1\StockTransferController::class, 'approve']);
        Route::post('stock-transfers/{stockTransfer}/reject', [API\V1\StockTransferController::class, 'reject']);
        Route::post('stock-transfers/{stockTransfer}/ship', [API\V1\StockTransferController::class, 'ship']);
        Route::post('stock-transfers/{stockTransfer}/receive', [API\V1\StockTransferController::class, 'receive']);
        Route::post('stock-transfers/{stockTransfer}/cancel', [API\V1\StockTransferController::class, 'cancel']);
        Route::apiResource('stock-transfers', API\V1\StockTransferController::class)->except(['update']);

        // ============================================================
        // V1 - Insurance Companies
        // ============================================================
        Route::apiResource('insurance-companies', API\V1\InsuranceCompanyController::class);

        // ============================================================
        // V1 - Insurance Plans
        // ============================================================
        Route::apiResource('insurance-plans', API\V1\InsurancePlanController::class);

        // ============================================================
        // V1 - Insurance Claims
        // ============================================================
        Route::apiResource('insurance-claims', API\V1\InsuranceClaimController::class);

        // ============================================================
        // V1 - Goods Received Notes
        // ============================================================
        Route::prefix('admin')->group(function () {
            Route::get('goods-received-notes', [\App\Http\Controllers\Admin\GoodsReceivedNoteController::class, 'index']);
            Route::post('goods-received-notes', [\App\Http\Controllers\Admin\GoodsReceivedNoteController::class, 'store']);
            Route::get('goods-received-notes/{goodsReceivedNote}', [\App\Http\Controllers\Admin\GoodsReceivedNoteController::class, 'show']);
            Route::delete('goods-received-notes/{goodsReceivedNote}', [\App\Http\Controllers\Admin\GoodsReceivedNoteController::class, 'destroy']);
        });

        Route::get('goods-received', [Api\GoodsReceivedNoteController::class, 'index']);
        Route::post('goods-received', [Api\GoodsReceivedNoteController::class, 'store']);
        Route::get('goods-received/{goodsReceivedNote}', [Api\GoodsReceivedNoteController::class, 'show']);

        // ============================================================
        // V1 - Orders (Sales Orders)
        // ============================================================
        Route::apiResource('orders', API\V1\OrderController::class);

        // ============================================================
        // V1 - Cash Register
        // ============================================================
        Route::get('cash-register/current', [API\V1\CashRegisterController::class, 'current']);
        Route::get('cash-register/summary', [API\V1\CashRegisterController::class, 'summary']);
        Route::post('cash-register/open', [API\V1\CashRegisterController::class, 'open']);
        Route::post('cash-register/{cashRegister}/close', [API\V1\CashRegisterController::class, 'close']);
        Route::apiResource('cash-register', API\V1\CashRegisterController::class)->only(['index']);

        // ============================================================
        // V1 - Stock Movements
        // ============================================================
        Route::get('stock-movements/history', [API\V1\StockMovementController::class, 'history']);
        Route::get('stock-movements/summary', [API\V1\StockMovementController::class, 'summary']);

        // ============================================================
        // V1 - Notifications
        // ============================================================
        Route::post('notifications/mark-read', [API\V1\NotificationController::class, 'markAsRead']);
        Route::post('notifications/mark-all-read', [API\V1\NotificationController::class, 'markAllAsRead']);
        Route::get('notifications/stats', [API\V1\NotificationController::class, 'stats']);
        Route::apiResource('notifications', API\V1\NotificationController::class)->only(['index', 'destroy']);

        // ============================================================
        // V1 - Attendance (Pharmacy Staff)
        // ============================================================
        Route::post('attendance/check-in', [API\V1\AttendanceController::class, 'checkIn']);
        Route::post('attendance/check-out', [API\V1\AttendanceController::class, 'checkOut']);
        Route::get('attendance/history', [API\V1\AttendanceController::class, 'history']);
        Route::get('attendance/statistics', [API\V1\AttendanceController::class, 'statistics']);
        Route::get('attendance/qr-token', [API\V1\AttendanceController::class, 'generateQrToken']);

        // ============================================================
        // V1 - Expiry Alerts & Demand Forecast
        // ============================================================
        Route::get('expiry-alerts', [API\V1\ExpiryAlertController::class, 'index']);
        Route::get('expiry-alerts/statistics', [API\V1\ExpiryAlertController::class, 'statistics']);
        Route::post('expiry-alerts/send', [API\V1\ExpiryAlertController::class, 'sendAlerts']);
        Route::get('reorder-suggestions', [API\V1\ExpiryAlertController::class, 'reorderSuggestions']);
        Route::get('demand-forecast', [API\V1\ExpiryAlertController::class, 'forecast']);

        // ============================================================
        // V1 - Fraud Detection
        // ============================================================
        Route::get('fraud/alerts', [API\V1\FraudDetectionController::class, 'index']);
        Route::get('fraud/suspicious-transactions', [API\V1\FraudDetectionController::class, 'suspiciousTransactions']);

        // ============================================================
        // V1 - Invoice OCR Processing
        // ============================================================
        Route::post('invoice-ocr/upload', [API\V1\InvoiceOcrController::class, 'upload']);
        Route::post('invoice-ocr/approve', [API\V1\InvoiceOcrController::class, 'approve']);

        // ============================================================
        // Payments - Digital Wallets & InstaPay
        // ============================================================
        Route::prefix('payments')->group(function () {
            Route::get('/methods', [Api\PaymentController::class, 'availableMethods']);
            Route::post('/initiate', [Api\PaymentController::class, 'initiate']);
            Route::post('/verify', [Api\PaymentController::class, 'verify']);
            Route::post('/refund', [Api\PaymentController::class, 'refund']);
            Route::get('/{payment}', [Api\PaymentController::class, 'show']);
            Route::get('/', [Api\PaymentController::class, 'index']);
            
            // POS-specific payment endpoints
            Route::post('/pos/quick-pay', [Api\PosPaymentController::class, 'quickPay']);
            Route::post('/pos/split-payment', [Api\PosPaymentController::class, 'splitPayment']);
        });
    });
});

// ============================================================
// Payment Webhooks (No Auth - Verified via HMAC)
// ============================================================
Route::prefix('webhooks/payments')->group(function () {
    Route::post('/paymob', [Api\PaymentWebhookController::class, 'handlePaymob']);
    Route::post('/{gateway}', [Api\PaymentWebhookController::class, 'handle'])->middleware('throttle:webhooks');
});
