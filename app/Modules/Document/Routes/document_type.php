<?php

use App\Modules\Document\DocumentTypeController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [DocumentTypeController::class, 'export'])->middleware('permission:document-types.export,web');
Route::post('/import', [DocumentTypeController::class, 'import'])->middleware('permission:document-types.import,web');
Route::post('/bulk-delete', [DocumentTypeController::class, 'bulkDestroy'])->middleware('permission:document-types.bulkDestroy,web');
Route::patch('/bulk-status', [DocumentTypeController::class, 'bulkUpdateStatus'])->middleware('permission:document-types.bulkUpdateStatus,web');
Route::get('/stats', [DocumentTypeController::class, 'stats'])->middleware('permission:document-types.stats,web');
Route::get('/', [DocumentTypeController::class, 'index'])->middleware('permission:document-types.index,web');
Route::get('/{documentType}', [DocumentTypeController::class, 'show'])->middleware('permission:document-types.show,web');
Route::post('/', [DocumentTypeController::class, 'store'])->middleware('permission:document-types.store,web');
Route::put('/{documentType}', [DocumentTypeController::class, 'update'])->middleware('permission:document-types.update,web');
Route::patch('/{documentType}', [DocumentTypeController::class, 'update'])->middleware('permission:document-types.update,web');
Route::delete('/{documentType}', [DocumentTypeController::class, 'destroy'])->middleware('permission:document-types.destroy,web');
Route::patch('/{documentType}/status', [DocumentTypeController::class, 'changeStatus'])->middleware('permission:document-types.changeStatus,web');
