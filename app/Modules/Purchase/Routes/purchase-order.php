<?php

use App\Modules\Purchase\Controllers\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

// Export (dat truoc {id})
Route::get('/export', [PurchaseOrderController::class, 'export']);

// CRUD
Route::get('/', [PurchaseOrderController::class, 'index']);
Route::post('/', [PurchaseOrderController::class, 'store']);
Route::get('/{id}', [PurchaseOrderController::class, 'show'])->where('id', '[0-9]+');
Route::put('/{id}', [PurchaseOrderController::class, 'update'])->where('id', '[0-9]+');

// Actions
Route::post('/{id}/reopen', [PurchaseOrderController::class, 'reopen'])->where('id', '[0-9]+');
Route::post('/{id}/complete', [PurchaseOrderController::class, 'complete'])->where('id', '[0-9]+');
Route::post('/{id}/cancel', [PurchaseOrderController::class, 'cancel'])->where('id', '[0-9]+');
Route::post('/{id}/copy', [PurchaseOrderController::class, 'copy'])->where('id', '[0-9]+');
