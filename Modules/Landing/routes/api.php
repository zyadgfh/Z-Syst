<?php

use Illuminate\Support\Facades\Route;
use Modules\Landing\App\Http\Controllers\Api\LandingController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

Route::prefix('v1')->dw
troller::class, 'index'])->name('landing');
    Route::get('landing/pricing', [LandingController::class, 'pricing'])->name('landing.pricing');
    Route::get('landing/features', [LandingController::class, 'features'])->name('landing.features');
    Route::get('landing/contact', [LandingController::class, 'contact'])->name('landing.contact');
});
