<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MortgageController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', [MortgageController::class, 'health']);


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    // Protected by authentication
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });
});

// Protected routes
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Mortgage calculation
    Route::post('/mortgage/calculate', [MortgageController::class, 'calculate']);

    // Simulations management
    Route::get('/simulations', [MortgageController::class, 'index']);
    Route::get('/simulations/{simulation}', [MortgageController::class, 'show']);
    Route::get('/simulations/{simulation}/export', [MortgageController::class, 'export']);
});
