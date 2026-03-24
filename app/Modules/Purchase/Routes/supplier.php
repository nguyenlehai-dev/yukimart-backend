<?php

use App\Modules\Purchase\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

// Export/Import
Route::get('/export', [SupplierController::class, 'export']);
Route::post('/import', [SupplierController::class, 'import']);

// Options
Route::get('/options', [SupplierController::class, 'options']);

// Nhom NCC
Route::get('/groups', [SupplierController::class, 'listGroups']);
Route::post('/groups', [SupplierController::class, 'storeGroup']);
Route::put('/groups/{groupId}', [SupplierController::class, 'updateGroup'])->where('groupId', '[0-9]+');
Route::delete('/groups/{groupId}', [SupplierController::class, 'destroyGroup'])->where('groupId', '[0-9]+');

// Bulk
Route::post('/bulk-delete', [SupplierController::class, 'bulkDestroy']);

// CRUD
Route::get('/', [SupplierController::class, 'index']);
Route::post('/', [SupplierController::class, 'store']);
Route::get('/{id}', [SupplierController::class, 'show'])->where('id', '[0-9]+');
Route::put('/{id}', [SupplierController::class, 'update'])->where('id', '[0-9]+');
Route::delete('/{id}', [SupplierController::class, 'destroy'])->where('id', '[0-9]+');

// Actions
Route::post('/{id}/toggle-status', [SupplierController::class, 'toggleStatus'])->where('id', '[0-9]+');
Route::get('/{id}/transactions', [SupplierController::class, 'transactionHistory'])->where('id', '[0-9]+');
Route::get('/{id}/debt', [SupplierController::class, 'debtHistory'])->where('id', '[0-9]+');
Route::post('/{id}/pay-debt', [SupplierController::class, 'payDebt'])->where('id', '[0-9]+');
Route::post('/{id}/discount', [SupplierController::class, 'applyDiscount'])->where('id', '[0-9]+');
Route::post('/{id}/adjust-debt', [SupplierController::class, 'adjustDebt'])->where('id', '[0-9]+');
