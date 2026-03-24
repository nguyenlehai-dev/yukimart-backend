<?php

use App\Modules\Product\Controllers\BrandController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BrandController::class, 'index']);
Route::get('/options', [BrandController::class, 'options']);
Route::post('/', [BrandController::class, 'store']);
Route::put('/{id}', [BrandController::class, 'update']);
Route::delete('/{id}', [BrandController::class, 'destroy']);
