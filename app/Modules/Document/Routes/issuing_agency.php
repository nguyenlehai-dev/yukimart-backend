<?php

use App\Modules\Document\IssuingAgencyController;
use Illuminate\Support\Facades\Route;

Route::get('/export', [IssuingAgencyController::class, 'export'])->middleware('permission:issuing-agencies.export,web');
Route::post('/import', [IssuingAgencyController::class, 'import'])->middleware('permission:issuing-agencies.import,web');
Route::post('/bulk-delete', [IssuingAgencyController::class, 'bulkDestroy'])->middleware('permission:issuing-agencies.bulkDestroy,web');
Route::patch('/bulk-status', [IssuingAgencyController::class, 'bulkUpdateStatus'])->middleware('permission:issuing-agencies.bulkUpdateStatus,web');
Route::get('/stats', [IssuingAgencyController::class, 'stats'])->middleware('permission:issuing-agencies.stats,web');
Route::get('/', [IssuingAgencyController::class, 'index'])->middleware('permission:issuing-agencies.index,web');
Route::get('/{issuingAgency}', [IssuingAgencyController::class, 'show'])->middleware('permission:issuing-agencies.show,web');
Route::post('/', [IssuingAgencyController::class, 'store'])->middleware('permission:issuing-agencies.store,web');
Route::put('/{issuingAgency}', [IssuingAgencyController::class, 'update'])->middleware('permission:issuing-agencies.update,web');
Route::patch('/{issuingAgency}', [IssuingAgencyController::class, 'update'])->middleware('permission:issuing-agencies.update,web');
Route::delete('/{issuingAgency}', [IssuingAgencyController::class, 'destroy'])->middleware('permission:issuing-agencies.destroy,web');
Route::patch('/{issuingAgency}/status', [IssuingAgencyController::class, 'changeStatus'])->middleware('permission:issuing-agencies.changeStatus,web');
