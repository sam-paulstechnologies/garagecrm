<?php

use App\Http\Controllers\QuickScanController;
use Illuminate\Support\Facades\Route;

Route::prefix('scan/{token}')->where(['token' => '[A-Za-z0-9]{64}'])->name('quick-scan.')->group(function (): void {
    Route::get('/', [QuickScanController::class, 'show'])->middleware('throttle:120,1')->name('show');
    Route::post('consent', [QuickScanController::class, 'consent'])->middleware('throttle:10,1')->name('consent');
    Route::post('meta/start', [QuickScanController::class, 'startMeta'])->middleware('throttle:5,1')->name('meta.start');
    Route::post('meta/complete', [QuickScanController::class, 'completeMeta'])->middleware('throttle:5,1')->name('meta.complete');
    Route::get('progress', [QuickScanController::class, 'progress'])->middleware('throttle:120,1')->name('progress');
    Route::post('accept', [QuickScanController::class, 'accept'])->middleware('throttle:5,1')->name('accept');
    Route::post('decline', [QuickScanController::class, 'decline'])->middleware('throttle:5,1')->name('decline');
});
