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

// Mortgage calculation with rate limiting
Route::middleware('auth:sanctum')->post('/mortgage/calculate', [MortgageController::class, 'calculate'])
    ->middleware('throttle:60,1');
