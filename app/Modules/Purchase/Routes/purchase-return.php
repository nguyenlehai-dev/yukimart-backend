<?php

use App\Modules\Purchase\Controllers\PurchaseReturnController;
use Illuminate\Support\Facades\Route;

// Export (dat truoc {id})
Route::get('/export', [PurchaseReturnController::class, 'export']);

// CRUD
Route::get('/', [PurchaseReturnController::class, 'index']);
Route::post('/quick', [PurchaseReturnController::class, 'storeQuick']);
Route::post('/from-order', [PurchaseReturnController::class, 'storeFromOrder']);
Route::get('/{id}', [PurchaseReturnController::class, 'show'])->where('id', '[0-9]+');
Route::put('/{id}', [PurchaseReturnController::class, 'update'])->where('id', '[0-9]+');

// Actions
Route::post('/{id}/cancel', [PurchaseReturnController::class, 'cancel'])->where('id', '[0-9]+');
Route::post('/{id}/copy', [PurchaseReturnController::class, 'copy'])->where('id', '[0-9]+');
