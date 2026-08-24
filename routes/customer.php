<?php

use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\CustomerAuthController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\CompareController;
use App\Http\Controllers\Customer\LoyaltyController;
use App\Http\Controllers\Customer\ReviewController;
use App\Http\Controllers\Customer\WishlistController;
use App\Models\Coupon;
use Illuminate\Support\Facades\Route;

// ── Customer Authentication ──
Route::middleware('guest')->group(function () {
    Route::get('/customer/register', [CustomerAuthController::class, 'showRegister'])->name('customer.register');
    Route::post('/customer/register', [CustomerAuthController::class, 'register'])->name('customer.register.store');
    Route::get('/customer/login', [CustomerAuthController::class, 'showLogin'])->name('customer.login');
    Route::post('/customer/login', [CustomerAuthController::class, 'login'])->name('customer.login.store');
});

Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])
    ->middleware('auth')
    ->name('customer.logout');

// ── Cart (guest + auth) ──
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::put('/cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');
Route::get('/cart/count', [CartController::class, 'count'])->name('cart.count');

// ── Customer Dashboard (auth required) ──
Route::middleware('auth')->prefix('customer')->name('customer.')->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/orders', [CustomerDashboardController::class, 'orders'])->name('orders');
    Route::get('/orders/{orderNumber}', [CustomerDashboardController::class, 'orderDetail'])->name('orders.show');
});

// ── Checkout (auth required) ──
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('checkout');
    Route::post('/checkout/place-order', [OrderController::class, 'placeOrder'])->name('checkout.place');
    Route::get('/order-confirmation/{orderNumber}', [OrderController::class, 'show'])->name('customer.orders.show');
});

// ── Wishlist (guest + auth) ──
Route::post('/wishlist/toggle/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index')->middleware('auth');
Route::delete('/wishlist/{wishlist}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

// ── Coupon Validation (for checkout AJAX) ──
Route::post('/checkout/validate-coupon', function (\Illuminate\Http\Request $request) {
    $request->validate([
        'code'     => 'required|string',
        'subtotal' => 'required|numeric|min:0',
    ]);
    $result = Coupon::validateAndApply($request->code, $request->subtotal, auth()->id());
    return response()->json($result);
});

// ── Product Reviews (auth required for write, public for read) ──
Route::middleware('auth')->group(function () {
    Route::get('/reviews/{productId}/create', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('/reviews/{productId}', [ReviewController::class, 'store'])->name('reviews.store');
    Route::post('/reviews/{review}/helpful', [ReviewController::class, 'helpful'])->name('reviews.helpful');
});

// ── Product Comparison (guest + auth) ──
Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
Route::get('/compare/history', [CompareController::class, 'history'])->name('compare.history');
Route::post('/compare/add/{product}', [CompareController::class, 'add'])->name('compare.add');
Route::post('/compare/remove/{product}', [CompareController::class, 'remove'])->name('compare.remove');
Route::post('/compare/clear', [CompareController::class, 'clear'])->name('compare.clear');
Route::get('/compare/count', [CompareController::class, 'count'])->name('compare.count');

// ── Loyalty Points (auth required) ──
Route::middleware('auth')->prefix('customer')->name('customer.')->group(function () {
    Route::get('/loyalty', [LoyaltyController::class, 'index'])->name('loyalty');
    Route::post('/loyalty/redeem', [LoyaltyController::class, 'redeem'])->name('loyalty.redeem');
});
