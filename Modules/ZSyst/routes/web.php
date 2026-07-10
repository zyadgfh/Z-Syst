<?php

use Illuminate\Support\Facades\Route;
use Modules\ZSyst\App\Http\Controllers\DashboardController;

Route::get('/zsyst', [DashboardController::class, 'index']);
