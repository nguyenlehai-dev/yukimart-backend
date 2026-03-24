<?php

use App\Modules\Document\DocumentController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [DocumentController::class, 'export'])->middleware('permission:documents.export,web');
Route::post('/import', [DocumentController::class, 'import'])->middleware('permission:documents.import,web');
Route::post('/bulk-delete', [DocumentController::class, 'bulkDestroy'])->middleware('permission:documents.bulkDestroy,web');
Route::patch('/bulk-status', [DocumentController::class, 'bulkUpdateStatus'])->middleware('permission:documents.bulkUpdateStatus,web');
Route::get('/stats', [DocumentController::class, 'stats'])->middleware('permission:documents.stats,web');
Route::get('/', [DocumentController::class, 'index'])->middleware('permission:documents.index,web');
Route::get('/{document}', [DocumentController::class, 'show'])->middleware('permission:documents.show,web');
Route::post('/', [DocumentController::class, 'store'])->middleware('permission:documents.store,web');
Route::put('/{document}', [DocumentController::class, 'update'])->middleware('permission:documents.update,web');
Route::patch('/{document}', [DocumentController::class, 'update'])->middleware('permission:documents.update,web');
Route::delete('/{document}', [DocumentController::class, 'destroy'])->middleware('permission:documents.destroy,web');
Route::patch('/{document}/status', [DocumentController::class, 'changeStatus'])->middleware('permission:documents.changeStatus,web');
