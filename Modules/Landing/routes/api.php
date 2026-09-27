<?php

use Illuminate\Support\Facades\Route;
use Modules\Landing\App\Http\Controllers\Api\LandingController;

Route::prefix('v1')->group(function () {
    Route::get('landing', [LandingController::class, 'index'])->name('landing');
    Route::get('landing/pricing', [LandingController::class, 'pricing'])->name('landing.pricing');
    Route::get('landing/features', [LandingController::class, 'features'])->name('landing.features');
    Route::get('landing/contact', [LandingController::class, 'contact'])->name('landing.contact');
});
