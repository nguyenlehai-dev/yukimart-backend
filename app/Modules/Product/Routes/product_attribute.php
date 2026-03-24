<?php

use App\Modules\Product\Controllers\ProductAttributeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductAttributeController::class, 'index']);
Route::post('/', [ProductAttributeController::class, 'store']);
Route::put('/{id}', [ProductAttributeController::class, 'update']);
Route::delete('/{id}', [ProductAttributeController::class, 'destroy']);
