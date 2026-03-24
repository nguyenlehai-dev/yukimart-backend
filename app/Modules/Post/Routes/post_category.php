<?php

use App\Modules\Post\PostCategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [PostCategoryController::class, 'export'])->middleware('permission:post-categories.export,web');
Route::post('/import', [PostCategoryController::class, 'import'])->middleware('permission:post-categories.import,web');
Route::post('/bulk-delete', [PostCategoryController::class, 'bulkDestroy'])->middleware('permission:post-categories.bulkDestroy,web');
Route::patch('/bulk-status', [PostCategoryController::class, 'bulkUpdateStatus'])->middleware('permission:post-categories.bulkUpdateStatus,web');
Route::get('/stats', [PostCategoryController::class, 'stats'])->middleware('permission:post-categories.stats,web');
Route::get('/tree', [PostCategoryController::class, 'tree'])->middleware('permission:post-categories.tree,web');
Route::get('/', [PostCategoryController::class, 'index'])->middleware('permission:post-categories.index,web');
Route::get('/{category}', [PostCategoryController::class, 'show'])->middleware('permission:post-categories.show,web');
Route::post('/', [PostCategoryController::class, 'store'])->middleware('permission:post-categories.store,web');
Route::put('/{category}', [PostCategoryController::class, 'update'])->middleware('permission:post-categories.update,web');
Route::patch('/{category}', [PostCategoryController::class, 'update'])->middleware('permission:post-categories.update,web');
Route::delete('/{category}', [PostCategoryController::class, 'destroy'])->middleware('permission:post-categories.destroy,web');
Route::patch('/{category}/status', [PostCategoryController::class, 'changeStatus'])->middleware('permission:post-categories.changeStatus,web');
