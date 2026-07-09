<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CompanyBranchLimitController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\Admin\ExpenseCategoryController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\InsuranceClaimController;
use App\Http\Controllers\Admin\InsuranceCompanyController;
use App\Http\Controllers\Admin\InsurancePlanController;
use App\Http\Controllers\Admin\ManufacturerController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PrescriptionController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductStockController;
use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\PurchaseOrderReturnController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StockTransferController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CashRegisterController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\V1\DrugController;
use App\Http\Controllers\Admin\GoodsReceivedNoteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:30,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:30,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:10,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1');

    // Public drug endpoints (tenant-scoped via HasCompany/global scope)
    Route::get('/drugs', [DrugController::class, 'index']);
    Route::get('/drugs/{drug}', [DrugController::class, 'show']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('/user', [AuthController::class, 'me'])->middleware('throttle:60,1');
        Route::post('/logout', [AuthController::class, 'logout'])->middleware('throttle:30,1');
        Route::post('/logout-all-devices', [AuthController::class, 'logoutAllDevices'])->middleware('throttle:30,1');
        Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('throttle:30,1');
        Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('throttle:30,1');
        Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('throttle:30,1');
        Route::post('/email/verification-notification', [AuthController::class, 'resendVerificationEmail'])->middleware('throttle:6,1');
        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware(['auth:sanctum', 'signed'])
            ->name('verification.verify');

        Route::post('/two-factor/setup', [TwoFactorController::class, 'setup'])->middleware(['auth:sanctum', 'throttle:10,1']);
        Route::post('/two-factor/confirm', [TwoFactorController::class, 'confirm'])->middleware(['auth:sanctum', 'throttle:10,1']);
        Route::post('/two-factor/disable', [TwoFactorController::class, 'disable'])->middleware(['auth:sanctum', 'throttle:10,1']);
        Route::post('/sales', [\App\Http\Controllers\Api\SaleController::class, 'store'])->middleware(['auth:sanctum','throttle:20,1']);

        // Dashboard & Analytics (for regular authenticated users)
        Route::prefix('dashboard')->group(function () {
            // KPIs
            Route::get('/kpis', [DashboardController::class, 'getKPIs'])->middleware('throttle:60,1');
            
            // Sales Analytics
            Route::get('/sales-trends', [DashboardController::class, 'getSalesTrends'])->middleware('throttle:60,1');
            Route::get('/top-selling-products', [DashboardController::class, 'getTopSellingProducts'])->middleware('throttle:60,1');
            Route::get('/sales-by-category', [DashboardController::class, 'getSalesByCategory'])->middleware('throttle:60,1');
            Route::get('/sales-by-branch', [DashboardController::class, 'getSalesByBranch'])->middleware('throttle:60,1');
            Route::get('/payment-method-breakdown', [DashboardController::class, 'getPaymentMethodBreakdown'])->middleware('throttle:60,1');
            
            // Inventory Analytics
            Route::get('/inventory-summary', [DashboardController::class, 'getInventorySummary'])->middleware('throttle:60,1');
            Route::get('/low-stock-products', [DashboardController::class, 'getLowStockProducts'])->middleware('throttle:60,1');
            Route::get('/out-of-stock-products', [DashboardController::class, 'getOutOfStockProducts'])->middleware('throttle:60,1');
            Route::get('/expiring-products', [DashboardController::class, 'getExpiringProducts'])->middleware('throttle:60,1');
            Route::get('/dead-stock', [DashboardController::class, 'getDeadStock'])->middleware('throttle:60,1');
            Route::get('/fast-moving-products', [DashboardController::class, 'getFastMovingProducts'])->middleware('throttle:60,1');
            Route::get('/stock-turnover', [DashboardController::class, 'getStockTurnover'])->middleware('throttle:60,1');
            
            // Cache Management
            Route::post('/clear-cache', [DashboardController::class, 'clearCache'])->middleware('throttle:30,1');
            
            // Report Exports
            Route::get('/export/sales', [DashboardController::class, 'exportSalesReport'])->middleware('throttle:10,1');
            Route::get('/export/inventory', [DashboardController::class, 'exportInventoryReport'])->middleware('throttle:10,1');
            Route::get('/export/expiring-products', [DashboardController::class, 'exportExpiringProductsReport'])->middleware('throttle:10,1');
            Route::get('/export/top-selling-products', [DashboardController::class, 'exportTopSellingProductsReport'])->middleware('throttle:10,1');
            Route::get('/export/stock-turnover', [DashboardController::class, 'exportStockTurnoverReport'])->middleware('throttle:10,1');
        });

        // Admin drug CRUD (tenant-enforced) - available to authenticated admin users
        Route::post('/drugs', [\App\Http\Controllers\Api\V1\Admin\DrugAdminController::class, 'store'])->middleware('throttle:30,1');
        Route::put('/drugs/{drug}', [\App\Http\Controllers\Api\V1\Admin\DrugAdminController::class, 'update'])->middleware('throttle:30,1');
        Route::delete('/drugs/{drug}', [\App\Http\Controllers\Api\V1\Admin\DrugAdminController::class, 'destroy'])->middleware('throttle:30,1');

    });

    Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
        // Admin drug CRUD (tenant-enforced) - admin-prefixed endpoints
        Route::get('/drugs', [\App\Http\Controllers\Api\V1\Admin\DrugAdminController::class, 'index'])->middleware('throttle:60,1');
        Route::post('/drugs', [\App\Http\Controllers\Api\V1\Admin\DrugAdminController::class, 'store'])->middleware('throttle:30,1');
        Route::put('/drugs/{drug}', [\App\Http\Controllers\Api\V1\Admin\DrugAdminController::class, 'update'])->middleware('throttle:30,1');
        Route::delete('/drugs/{drug}', [\App\Http\Controllers\Api\V1\Admin\DrugAdminController::class, 'destroy'])->middleware('throttle:30,1');
        Route::post('/import/products/json', [\App\Http\Controllers\Api\V1\Admin\ProductImportController::class, 'importJson'])->middleware('throttle:10,1');
        // Phase-2: Orders & Procurement
        Route::get('/orders', [\App\Http\Controllers\Api\V1\Admin\OrderController::class, 'index'])->middleware('throttle:60,1');
        Route::post('/orders', [\App\Http\Controllers\Api\V1\Admin\OrderController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/orders/{order}', [\App\Http\Controllers\Api\V1\Admin\OrderController::class, 'show'])->middleware('throttle:60,1');
        Route::put('/orders/{order}', [\App\Http\Controllers\Api\V1\Admin\OrderController::class, 'update'])->middleware('throttle:30,1');
        Route::delete('/orders/{order}', [\App\Http\Controllers\Api\V1\Admin\OrderController::class, 'destroy'])->middleware('throttle:30,1');

        // Role Management
        Route::middleware(['can:super-admin'])->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->middleware('throttle:60,1');
            Route::post('/roles', [RoleController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/roles/{role}', [RoleController::class, 'show'])->middleware('throttle:60,1');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('throttle:30,1');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('throttle:30,1');
            Route::post('/roles/{role}/permissions', [RoleController::class, 'assignPermissions'])->middleware('throttle:30,1');

            // Permission Management
            Route::get('/permissions', [PermissionController::class, 'index'])->middleware('throttle:60,1');
            Route::post('/permissions', [PermissionController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/permissions/{permission}', [PermissionController::class, 'show'])->middleware('throttle:60,1');
            Route::put('/permissions/{permission}', [PermissionController::class, 'update'])->middleware('throttle:30,1');
            Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy'])->middleware('throttle:30,1');
            Route::get('/permissions/modules', [PermissionController::class, 'modules'])->middleware('throttle:60,1');
            Route::get('/permissions/groups', [PermissionController::class, 'groups'])->middleware('throttle:60,1');

            // User Management
            Route::get('/users', [UserController::class, 'index'])->middleware('throttle:60,1');
            Route::post('/users', [UserController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/users/{user}', [UserController::class, 'show'])->middleware('throttle:60,1');
            Route::put('/users/{user}', [UserController::class, 'update'])->middleware('throttle:30,1');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('throttle:30,1');
            Route::post('/users/{user}/roles', [UserController::class, 'assignRoles'])->middleware('throttle:30,1');
            Route::delete('/users/{user}/roles/{role}', [UserController::class, 'removeRole'])->middleware('throttle:30,1');

            // Activity Logs
            Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware('throttle:60,1');
            Route::get('/activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->middleware('throttle:60,1');

            // Companies
            Route::get('/companies', [CompanyBranchLimitController::class, 'index'])->middleware('throttle:60,1');
            Route::get('/companies/{company}', [CompanyBranchLimitController::class, 'show'])->middleware('throttle:60,1');
            Route::post('/companies/bulk-update-branch-limits', [CompanyBranchLimitController::class, 'bulkUpdate'])->middleware('throttle:30,1');
            Route::put('/companies/{company}/branch-limit', [CompanyBranchLimitController::class, 'update'])->middleware('throttle:30,1');
            Route::get('/companies/{company}/branch-availability', [CompanyBranchLimitController::class, 'checkAvailability'])->middleware('throttle:60,1');

            // Product categories
            Route::get('/product-categories', [ProductCategoryController::class, 'index'])->middleware('throttle:60,1');
            Route::post('/product-categories', [ProductCategoryController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/product-categories/{product_category}', [ProductCategoryController::class, 'show'])->middleware('throttle:60,1');
            Route::put('/product-categories/{product_category}', [ProductCategoryController::class, 'update'])->middleware('throttle:30,1');
            Route::delete('/product-categories/{product_category}', [ProductCategoryController::class, 'destroy'])->middleware('throttle:30,1');

            // Products
            Route::get('/products', [ProductController::class, 'index'])->middleware('throttle:60,1');
            Route::post('/products', [ProductController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/products/{product}', [ProductController::class, 'show'])->middleware('throttle:60,1');
            Route::put('/products/{product}', [ProductController::class, 'update'])->middleware('throttle:30,1');
            Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('throttle:30,1');

            // Product stocks
            Route::get('/product-stocks', [ProductStockController::class, 'index'])->middleware('throttle:60,1');
            Route::post('/product-stocks', [ProductStockController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/product-stocks/{product_stock}', [ProductStockController::class, 'show'])->middleware('throttle:60,1');
            Route::put('/product-stocks/{product_stock}', [ProductStockController::class, 'update'])->middleware('throttle:30,1');
            Route::delete('/product-stocks/{product_stock}', [ProductStockController::class, 'destroy'])->middleware('throttle:30,1');

            // Stock transfers
            Route::get('/stock-transfers', [StockTransferController::class, 'index'])->middleware('throttle:60,1');
            Route::post('/stock-transfers', [StockTransferController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/stock-transfers/statistics', [StockTransferController::class, 'statistics'])->middleware('throttle:60,1');
            Route::get('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'show'])->middleware('throttle:60,1');
            Route::put('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'update'])->middleware('throttle:30,1');
            Route::delete('/stock-transfers/{stockTransfer}', [StockTransferController::class, 'destroy'])->middleware('throttle:30,1');
            Route::get('/stock-transfers/{stockTransfer}/items', [StockTransferController::class, 'items'])->middleware('throttle:60,1');
            Route::post('/stock-transfers/{stockTransfer}/approve', [StockTransferController::class, 'approve'])->middleware('throttle:30,1');
            Route::post('/stock-transfers/{stockTransfer}/reject', [StockTransferController::class, 'reject'])->middleware('throttle:30,1');
            Route::post('/stock-transfers/{stockTransfer}/ship', [StockTransferController::class, 'ship'])->middleware('throttle:30,1');
            Route::post('/stock-transfers/{stockTransfer}/receive', [StockTransferController::class, 'receive'])->middleware('throttle:30,1');
            Route::post('/stock-transfers/{stockTransfer}/cancel', [StockTransferController::class, 'cancel'])->middleware('throttle:30,1');

            // Manufacturers
            Route::get('/manufacturers', [ManufacturerController::class, 'index'])->middleware('throttle:60,1');
            Route::post('/manufacturers', [ManufacturerController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/manufacturers/{manufacturer}', [ManufacturerController::class, 'show'])->middleware('throttle:60,1');
            Route::put('/manufacturers/{manufacturer}', [ManufacturerController::class, 'update'])->middleware('throttle:30,1');
            Route::delete('/manufacturers/{manufacturer}', [ManufacturerController::class, 'destroy'])->middleware('throttle:30,1');

            // Patients
            Route::get('/patients', [PatientController::class, 'index'])->middleware('throttle:60,1');
            Route::post('/patients', [PatientController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/patients/{patient}', [PatientController::class, 'show'])->middleware('throttle:60,1');
            Route::put('/patients/{patient}', [PatientController::class, 'update'])->middleware('throttle:30,1');
            Route::delete('/patients/{patient}', [PatientController::class, 'destroy'])->middleware('throttle:30,1');

            // Doctors
            Route::get('/doctors', [DoctorController::class, 'index'])->middleware('throttle:60,1');
            Route::post('/doctors', [DoctorController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->middleware('throttle:60,1');
            Route::put('/doctors/{doctor}', [DoctorController::class, 'update'])->middleware('throttle:30,1');
            Route::delete('/doctors/{doctor}', [DoctorController::class, 'destroy'])->middleware('throttle:30,1');

            // Suppliers
            Route::get('/suppliers', [SupplierController::class, 'index'])->middleware('throttle:60,1');
            Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('throttle:30,1');
            Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->middleware('throttle:60,1');
            Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('throttle:30,1');
            Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('throttle:30,1');
        });

        // Purchase Orders
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->middleware('throttle:60,1');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->middleware('throttle:60,1');
        Route::put('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->middleware('throttle:30,1');
        Route::delete('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->middleware('throttle:30,1');
        Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->middleware('throttle:30,1');
        Route::post('/purchase-orders/{purchaseOrder}/send', [PurchaseOrderController::class, 'send'])->middleware('throttle:30,1');
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel'])->middleware('throttle:30,1');

        // Purchase Order Returns
        Route::get('/purchase-order-returns', [PurchaseOrderReturnController::class, 'index'])->middleware('throttle:60,1');
        Route::post('/purchase-order-returns', [PurchaseOrderReturnController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/purchase-order-returns/{purchaseOrderReturn}', [PurchaseOrderReturnController::class, 'show'])->middleware('throttle:60,1');
        Route::delete('/purchase-order-returns/{purchaseOrderReturn}', [PurchaseOrderReturnController::class, 'destroy'])->middleware('throttle:30,1');

        // Goods Received Notes
        Route::get('/goods-received-notes', [GoodsReceivedNoteController::class, 'index'])->middleware('throttle:60,1');
        Route::post('/goods-received-notes', [GoodsReceivedNoteController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/goods-received-notes/{goodsReceivedNote}', [GoodsReceivedNoteController::class, 'show'])->middleware('throttle:60,1');
        Route::delete('/goods-received-notes/{goodsReceivedNote}', [GoodsReceivedNoteController::class, 'destroy'])->middleware('throttle:30,1');

        // Prescriptions
        Route::get('/prescriptions', [PrescriptionController::class, 'index'])->middleware('throttle:60,1');
        Route::post('/prescriptions', [PrescriptionController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show'])->middleware('throttle:60,1');
        Route::put('/prescriptions/{prescription}', [PrescriptionController::class, 'update'])->middleware('throttle:30,1');
        Route::delete('/prescriptions/{prescription}', [PrescriptionController::class, 'destroy'])->middleware('throttle:30,1');
        Route::post('/prescriptions/{prescription}/dispense', [PrescriptionController::class, 'dispense'])->middleware('throttle:30,1');

        // Expense Categories
        Route::get('/expense-categories', [ExpenseCategoryController::class, 'index'])->middleware('throttle:60,1');
        Route::post('/expense-categories', [ExpenseCategoryController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'show'])->middleware('throttle:60,1');
        Route::put('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update'])->middleware('throttle:30,1');
        Route::delete('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->middleware('throttle:30,1');

        // Expenses
        Route::get('/expenses', [ExpenseController::class, 'index'])->middleware('throttle:60,1');
        Route::post('/expenses', [ExpenseController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/expenses/{expense}', [ExpenseController::class, 'show'])->middleware('throttle:60,1');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->middleware('throttle:30,1');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->middleware('throttle:30,1');

        // Cash Registers
        Route::get('/cash-registers', [CashRegisterController::class, 'index'])->middleware('throttle:60,1');
        Route::post('/cash-registers', [CashRegisterController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/cash-registers/{cashRegister}', [CashRegisterController::class, 'show'])->middleware('throttle:60,1');
        Route::post('/cash-registers/{cashRegister}/close', [CashRegisterController::class, 'close'])->middleware('throttle:30,1');

        // Insurance Companies
        Route::get('/insurance-companies', [InsuranceCompanyController::class, 'index'])->middleware('throttle:60,1');
        Route::post('/insurance-companies', [InsuranceCompanyController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/insurance-companies/{insuranceCompany}', [InsuranceCompanyController::class, 'show'])->middleware('throttle:60,1');
        Route::put('/insurance-companies/{insuranceCompany}', [InsuranceCompanyController::class, 'update'])->middleware('throttle:30,1');
        Route::delete('/insurance-companies/{insuranceCompany}', [InsuranceCompanyController::class, 'destroy'])->middleware('throttle:30,1');

        // Insurance Plans
        Route::get('/insurance-plans', [InsurancePlanController::class, 'index'])->middleware('throttle:60,1');
        Route::post('/insurance-plans', [InsurancePlanController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/insurance-plans/{insurancePlan}', [InsurancePlanController::class, 'show'])->middleware('throttle:60,1');
        Route::put('/insurance-plans/{insurancePlan}', [InsurancePlanController::class, 'update'])->middleware('throttle:30,1');
        Route::delete('/insurance-plans/{insurancePlan}', [InsurancePlanController::class, 'destroy'])->middleware('throttle:30,1');

        // Insurance Claims
        Route::get('/insurance-claims', [InsuranceClaimController::class, 'index'])->middleware('throttle:60,1');
        Route::post('/insurance-claims', [InsuranceClaimController::class, 'store'])->middleware('throttle:30,1');
        Route::get('/insurance-claims/{insuranceClaim}', [InsuranceClaimController::class, 'show'])->middleware('throttle:60,1');
        Route::put('/insurance-claims/{insuranceClaim}', [InsuranceClaimController::class, 'update'])->middleware('throttle:30,1');
        Route::delete('/insurance-claims/{insuranceClaim}', [InsuranceClaimController::class, 'destroy'])->middleware('throttle:30,1');
        Route::post('/insurance-claims/{insuranceClaim}/submit', [InsuranceClaimController::class, 'submit'])->middleware('throttle:30,1');
        Route::post('/insurance-claims/{insuranceClaim}/approve', [InsuranceClaimController::class, 'approve'])->middleware('throttle:30,1');
        Route::post('/insurance-claims/{insuranceClaim}/reject', [InsuranceClaimController::class, 'reject'])->middleware('throttle:30,1');
    });
});


