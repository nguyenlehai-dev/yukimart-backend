<?php

use App\Modules\Document\DocumentFieldController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [DocumentFieldController::class, 'export'])->middleware('permission:document-fields.export,web');
Route::post('/import', [DocumentFieldController::class, 'import'])->middleware('permission:document-fields.import,web');
Route::post('/bulk-delete', [DocumentFieldController::class, 'bulkDestroy'])->middleware('permission:document-fields.bulkDestroy,web');
Route::patch('/bulk-status', [DocumentFieldController::class, 'bulkUpdateStatus'])->middleware('permission:document-fields.bulkUpdateStatus,web');
Route::get('/stats', [DocumentFieldController::class, 'stats'])->middleware('permission:document-fields.stats,web');
Route::get('/', [DocumentFieldController::class, 'index'])->middleware('permission:document-fields.index,web');
Route::get('/{documentField}', [DocumentFieldController::class, 'show'])->middleware('permission:document-fields.show,web');
Route::post('/', [DocumentFieldController::class, 'store'])->middleware('permission:document-fields.store,web');
Route::put('/{documentField}', [DocumentFieldController::class, 'update'])->middleware('permission:document-fields.update,web');
Route::patch('/{documentField}', [DocumentFieldController::class, 'update'])->middleware('permission:document-fields.update,web');
Route::delete('/{documentField}', [DocumentFieldController::class, 'destroy'])->middleware('permission:document-fields.destroy,web');
Route::patch('/{documentField}/status', [DocumentFieldController::class, 'changeStatus'])->middleware('permission:document-fields.changeStatus,web');
