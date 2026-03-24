<?php

use App\Modules\Document\IssuingLevelController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [IssuingLevelController::class, 'export'])->middleware('permission:issuing-levels.export,web');
Route::post('/import', [IssuingLevelController::class, 'import'])->middleware('permission:issuing-levels.import,web');
Route::post('/bulk-delete', [IssuingLevelController::class, 'bulkDestroy'])->middleware('permission:issuing-levels.bulkDestroy,web');
Route::patch('/bulk-status', [IssuingLevelController::class, 'bulkUpdateStatus'])->middleware('permission:issuing-levels.bulkUpdateStatus,web');
Route::get('/stats', [IssuingLevelController::class, 'stats'])->middleware('permission:issuing-levels.stats,web');
Route::get('/', [IssuingLevelController::class, 'index'])->middleware('permission:issuing-levels.index,web');
Route::get('/{issuingLevel}', [IssuingLevelController::class, 'show'])->middleware('permission:issuing-levels.show,web');
Route::post('/', [IssuingLevelController::class, 'store'])->middleware('permission:issuing-levels.store,web');
Route::put('/{issuingLevel}', [IssuingLevelController::class, 'update'])->middleware('permission:issuing-levels.update,web');
Route::patch('/{issuingLevel}', [IssuingLevelController::class, 'update'])->middleware('permission:issuing-levels.update,web');
Route::delete('/{issuingLevel}', [IssuingLevelController::class, 'destroy'])->middleware('permission:issuing-levels.destroy,web');
Route::patch('/{issuingLevel}/status', [IssuingLevelController::class, 'changeStatus'])->middleware('permission:issuing-levels.changeStatus,web');
