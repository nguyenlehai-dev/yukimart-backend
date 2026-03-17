<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| YukiMart API Routes
|--------------------------------------------------------------------------
*/

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Product routes
Route::apiResource('products', ProductController::class);

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'app' => 'YukiMart API',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]);
});
