<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CompanyBranchLimitController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductStockController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TwoFactorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:30,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:30,1');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:10,1');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,1');

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
    });

    Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
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
        });
    });
});


