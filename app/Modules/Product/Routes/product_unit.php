<?php

use App\Modules\Product\Controllers\ProductUnitController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductUnitController::class, 'index']);
Route::get('/options', [ProductUnitController::class, 'options']);
Route::post('/', [ProductUnitController::class, 'store']);
Route::put('/{id}', [ProductUnitController::class, 'update']);
Route::delete('/{id}', [ProductUnitController::class, 'destroy']);
