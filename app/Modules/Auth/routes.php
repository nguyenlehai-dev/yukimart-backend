<?php

/**
 * Routes cho Module Auth
 *
 * Public: login, register, csrf-cookie (rate limit)
 * Protected: logout, me, change-password (cần token)
 */

use App\Modules\Auth\Controllers\AuthController;
use App\Modules\Auth\Middleware\AuthenticateToken;
use Illuminate\Support\Facades\Route;

// ── Routes công khai ──
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1'); // 5 lần/phút

    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:3,1'); // 3 lần/phút

    Route::get('/csrf-cookie', [AuthController::class, 'csrfCookie']);
});

// ── Routes cần đăng nhập ──
Route::prefix('auth')
    ->middleware(AuthenticateToken::class)
    ->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/password', [AuthController::class, 'changePassword']);
    });
