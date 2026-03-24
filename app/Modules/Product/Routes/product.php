<?php

use App\Modules\Product\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

// Export / Import (đặt trước route {id} để tránh xung đột)
Route::get('/export', [ProductController::class, 'export']);
Route::post('/import', [ProductController::class, 'import']);

// Bulk actions (đặt trước route {id})
Route::put('/bulk-toggle-active', [ProductController::class, 'bulkToggleActive']);
Route::put('/bulk-category', [ProductController::class, 'bulkCategory']);
Route::put('/bulk-point', [ProductController::class, 'bulkPoint']);
Route::delete('/bulk-delete', [ProductController::class, 'bulkDelete']);

// CRUD cơ bản
Route::get('/', [ProductController::class, 'index']);
Route::post('/', [ProductController::class, 'store']);
Route::get('/{id}', [ProductController::class, 'show'])->where('id', '[0-9]+');
Route::put('/{id}', [ProductController::class, 'update'])->where('id', '[0-9]+');
Route::post('/{id}/update', [ProductController::class, 'update'])->where('id', '[0-9]+');
Route::delete('/{id}', [ProductController::class, 'destroy'])->where('id', '[0-9]+');

// Actions
Route::post('/{id}/copy', [ProductController::class, 'copy'])->where('id', '[0-9]+');
Route::put('/{id}/toggle-active', [ProductController::class, 'toggleActive'])->where('id', '[0-9]+');

// Tồn kho & Phân tích
Route::get('/{id}/inventory', [ProductController::class, 'inventory'])->where('id', '[0-9]+');
Route::get('/{id}/stock-card', [ProductController::class, 'stockCard'])->where('id', '[0-9]+');
Route::get('/{id}/analytics', [ProductController::class, 'analytics'])->where('id', '[0-9]+');
