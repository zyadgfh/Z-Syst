<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'Z-Syst API', 'version' => '1.0.0']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard', function () {
        return response()->json(['message' => 'Dashboard']);
    });
});
