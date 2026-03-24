<?php

use App\Modules\Product\Controllers\ProductCategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductCategoryController::class, 'index']);
Route::get('/tree', [ProductCategoryController::class, 'tree']);
Route::get('/options', [ProductCategoryController::class, 'options']);
Route::get('/{id}', [ProductCategoryController::class, 'show']);
Route::post('/', [ProductCategoryController::class, 'store']);
Route::put('/{id}', [ProductCategoryController::class, 'update']);
Route::delete('/{id}', [ProductCategoryController::class, 'destroy']);
