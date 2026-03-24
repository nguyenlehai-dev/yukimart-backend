<?php

use App\Modules\Inventory\Controllers\StockDisposalController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [StockDisposalController::class, 'export']);

Route::get('/', [StockDisposalController::class, 'index']);
Route::post('/', [StockDisposalController::class, 'store']);
Route::get('/{id}', [StockDisposalController::class, 'show'])->where('id', '[0-9]+');
Route::put('/{id}', [StockDisposalController::class, 'update'])->where('id', '[0-9]+');

Route::post('/{id}/cancel', [StockDisposalController::class, 'cancel'])->where('id', '[0-9]+');
Route::post('/{id}/complete', [StockDisposalController::class, 'complete'])->where('id', '[0-9]+');
Route::post('/{id}/copy', [StockDisposalController::class, 'copy'])->where('id', '[0-9]+');
