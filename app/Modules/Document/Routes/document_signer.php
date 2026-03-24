<?php

use App\Modules\Document\DocumentSignerController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [DocumentSignerController::class, 'export'])->middleware('permission:document-signers.export,web');
Route::post('/import', [DocumentSignerController::class, 'import'])->middleware('permission:document-signers.import,web');
Route::post('/bulk-delete', [DocumentSignerController::class, 'bulkDestroy'])->middleware('permission:document-signers.bulkDestroy,web');
Route::patch('/bulk-status', [DocumentSignerController::class, 'bulkUpdateStatus'])->middleware('permission:document-signers.bulkUpdateStatus,web');
Route::get('/stats', [DocumentSignerController::class, 'stats'])->middleware('permission:document-signers.stats,web');
Route::get('/', [DocumentSignerController::class, 'index'])->middleware('permission:document-signers.index,web');
Route::get('/{documentSigner}', [DocumentSignerController::class, 'show'])->middleware('permission:document-signers.show,web');
Route::post('/', [DocumentSignerController::class, 'store'])->middleware('permission:document-signers.store,web');
Route::put('/{documentSigner}', [DocumentSignerController::class, 'update'])->middleware('permission:document-signers.update,web');
Route::patch('/{documentSigner}', [DocumentSignerController::class, 'update'])->middleware('permission:document-signers.update,web');
Route::delete('/{documentSigner}', [DocumentSignerController::class, 'destroy'])->middleware('permission:document-signers.destroy,web');
Route::patch('/{documentSigner}/status', [DocumentSignerController::class, 'changeStatus'])->middleware('permission:document-signers.changeStatus,web');
