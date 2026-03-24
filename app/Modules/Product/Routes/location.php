<?php

use App\Modules\Product\Controllers\LocationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LocationController::class, 'index']);
Route::get('/options', [LocationController::class, 'options']);
Route::post('/', [LocationController::class, 'store']);
Route::put('/{id}', [LocationController::class, 'update']);
Route::delete('/{id}', [LocationController::class, 'destroy']);
