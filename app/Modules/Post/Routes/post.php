<?php

use App\Modules\Post\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [PostController::class, 'export'])->middleware('permission:posts.export,web');
Route::post('/import', [PostController::class, 'import'])->middleware('permission:posts.import,web');
Route::post('/bulk-delete', [PostController::class, 'bulkDestroy'])->middleware('permission:posts.bulkDestroy,web');
Route::patch('/bulk-status', [PostController::class, 'bulkUpdateStatus'])->middleware('permission:posts.bulkUpdateStatus,web');
Route::get('/stats', [PostController::class, 'stats'])->middleware('permission:posts.stats,web');
Route::get('/', [PostController::class, 'index'])->middleware('permission:posts.index,web');
Route::get('/{post}', [PostController::class, 'show'])->middleware('permission:posts.show,web');
Route::post('/{post}/view', [PostController::class, 'incrementView'])->middleware('permission:posts.incrementView,web');
Route::post('/', [PostController::class, 'store'])->middleware('permission:posts.store,web');
Route::put('/{post}', [PostController::class, 'update'])->middleware('permission:posts.update,web');
Route::patch('/{post}', [PostController::class, 'update'])->middleware('permission:posts.update,web');
Route::delete('/{post}', [PostController::class, 'destroy'])->middleware('permission:posts.destroy,web');
Route::patch('/{post}/status', [PostController::class, 'changeStatus'])->middleware('permission:posts.changeStatus,web');
