<?php

use App\Http\Controllers\Api\MortgageController;
use Illuminate\Support\Facades\Route;

// Health check
Route::get('/health', [MortgageController::class, 'health']);

// Mortgage calculation com rate limiting
Route::post('/mortgage/calculate', [MortgageController::class, 'calculate'])
    ->middleware('throttle:60,1');
