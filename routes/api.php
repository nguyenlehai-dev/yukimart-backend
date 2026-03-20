<?php

use App\Helpers\ApiResponse;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| YukiMart API Routes (Global)
|--------------------------------------------------------------------------
|
| Routes của từng module nằm trong app/Modules/{Module}/routes.php
| và được ModuleServiceProvider tự động load.
|
| File này chỉ chứa routes không thuộc module nào.
|
*/

// ── Health Check ──
Route::get('/health', function () {
    return ApiResponse::success([
        'status' => 'ok',
        'app' => 'YukiMart API',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString(),
    ]);
});
