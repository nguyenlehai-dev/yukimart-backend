<?php

use App\Modules\Product\Controllers\PriceListController;
use Illuminate\Support\Facades\Route;

// So sánh nhiều bảng giá (đặt trước {id})
Route::get('/compare', [PriceListController::class, 'compare']);

// CRUD
Route::get('/', [PriceListController::class, 'index']);
Route::post('/', [PriceListController::class, 'store']);
Route::get('/{id}', [PriceListController::class, 'show'])->where('id', '[0-9]+');
Route::put('/{id}', [PriceListController::class, 'update'])->where('id', '[0-9]+');
Route::delete('/{id}', [PriceListController::class, 'destroy'])->where('id', '[0-9]+');

// Items: thêm/sửa/xóa sản phẩm trong bảng giá
Route::post('/{id}/items', [PriceListController::class, 'upsertItems'])->where('id', '[0-9]+');
Route::delete('/{id}/items', [PriceListController::class, 'removeItems'])->where('id', '[0-9]+');
Route::post('/{id}/add-all', [PriceListController::class, 'addAllProducts'])->where('id', '[0-9]+');
Route::post('/{id}/add-by-category', [PriceListController::class, 'addByCategory'])->where('id', '[0-9]+');

// Công thức & Export
Route::post('/{id}/apply-formula', [PriceListController::class, 'applyFormula'])->where('id', '[0-9]+');
Route::get('/{id}/export', [PriceListController::class, 'export'])->where('id', '[0-9]+');
