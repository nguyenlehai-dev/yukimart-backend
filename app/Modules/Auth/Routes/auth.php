<?php

use App\Modules\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Rate limit chống brute force / spam đăng ký:
// - login: 5 lần thất bại / phút / IP (Laravel auto count theo middleware throttle).
// - register: 10 lần / giờ / IP.
// - forgot-password: 3 lần / 10 phút / IP để chống abuse gửi email.
Route::middleware('throttle:5,1')->post('/login', [AuthController::class, 'login']);
Route::middleware('throttle:10,60')->post('/register', [AuthController::class, 'register']);
Route::middleware('throttle:3,10')->post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::middleware('throttle:10,60')->post('/reset-password', [AuthController::class, 'resetPassword']);
// Verify email — link từ mail có signed URL, đặt name để URL::temporarySignedRoute resolve.
Route::middleware('throttle:30,60')
    ->get('/verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->name('auth.verify-email');
Route::middleware(['auth:sanctum', 'throttle:6,60'])->post('/resend-verification', [AuthController::class, 'resendVerification']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/avatar', [AuthController::class, 'updateAvatar'])->middleware('auth:sanctum');
Route::post('/switch-organization', [AuthController::class, 'switchOrganization'])->middleware('auth:sanctum');
