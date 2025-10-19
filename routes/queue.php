<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\QueueController;

Route::middleware(['web','auth']) // add your admin gate/middleware here (e.g., 'can:admin')
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/queue', [QueueController::class, 'index'])->name('queue.index');
        Route::post('/queue/retry/{id}', [QueueController::class, 'retry'])->name('queue.retry');
        Route::post('/queue/forget/{id}', [QueueController::class, 'forget'])->name('queue.forget');
        Route::post('/queue/retry-all', [QueueController::class, 'retryAll'])->name('queue.retryAll');
        Route::post('/queue/flush', [QueueController::class, 'flush'])->name('queue.flush');
    });
