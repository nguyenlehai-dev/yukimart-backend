<?php

use App\Modules\Purchase\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SupplierController::class, 'index']);
Route::get('/options', [SupplierController::class, 'options']);
Route::post('/', [SupplierController::class, 'store']);
Route::get('/{id}', [SupplierController::class, 'show'])->where('id', '[0-9]+');
Route::put('/{id}', [SupplierController::class, 'update'])->where('id', '[0-9]+');
Route::delete('/{id}', [SupplierController::class, 'destroy'])->where('id', '[0-9]+');
