<?php

use App\Modules\Inventory\Controllers\StockCheckController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [StockCheckController::class, 'export']);
Route::post('/merge', [StockCheckController::class, 'merge']);

Route::get('/', [StockCheckController::class, 'index']);
Route::post('/', [StockCheckController::class, 'store']);
Route::get('/{id}', [StockCheckController::class, 'show'])->where('id', '[0-9]+');
Route::put('/{id}', [StockCheckController::class, 'update'])->where('id', '[0-9]+');

Route::post('/{id}/balance', [StockCheckController::class, 'balance'])->where('id', '[0-9]+');
Route::post('/{id}/cancel', [StockCheckController::class, 'cancel'])->where('id', '[0-9]+');
Route::post('/{id}/copy', [StockCheckController::class, 'copy'])->where('id', '[0-9]+');
