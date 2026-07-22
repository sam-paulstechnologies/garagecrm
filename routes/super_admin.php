<?php

use App\Http\Controllers\SuperAdmin\AuditController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\GarageController;
use App\Http\Controllers\SuperAdmin\LogController;
use App\Http\Controllers\SuperAdmin\OperationsCenterController;
use App\Http\Controllers\SuperAdmin\SystemHealthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active', 'force_password', 'role:super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('garages', [GarageController::class, 'index'])->name('garages.index');
        Route::get('garages/{garage}', [GarageController::class, 'show'])->name('garages.show');
        Route::patch('garages/{garage}', [GarageController::class, 'update'])->name('garages.update');
        Route::post('garages/{garage}/activate', [GarageController::class, 'activate'])->name('garages.activate');
        Route::post('garages/{garage}/suspend', [GarageController::class, 'suspend'])->name('garages.suspend');
        Route::get('garages/{garage}/users', [GarageController::class, 'users'])->name('garages.users');
        Route::get('garages/{garage}/modules', [GarageController::class, 'modules'])->name('garages.modules');
        Route::patch('garages/{garage}/modules', [GarageController::class, 'updateModule'])->name('garages.modules.update');
        Route::get('garages/{garage}/channels', [GarageController::class, 'channels'])->name('garages.channels');

        Route::get('logs/messages', [LogController::class, 'messages'])->name('logs.messages');
        Route::get('logs/leads', [LogController::class, 'leads'])->name('logs.leads');
        Route::get('system/health', SystemHealthController::class)->name('system.health');
        Route::get('audit', [AuditController::class, 'index'])->name('audit.index');

        Route::prefix('operations-center')->name('operations.')->group(function () {
            Route::redirect('/', '/super-admin/operations-center/journey-flow')->name('index');
            Route::get('{view}', [OperationsCenterController::class, 'view'])
                ->whereIn('view', ['journey-flow', 'mind-map', 'technical-map'])
                ->name('view');
            Route::get('api/graph/data', [OperationsCenterController::class, 'data'])->name('data');
            Route::get('api/graph/branch', [OperationsCenterController::class, 'branch'])->name('branch');
            Route::get('api/graph/search', [OperationsCenterController::class, 'search'])->name('search');
            Route::get('api/graph/trace', [OperationsCenterController::class, 'trace'])->name('trace');
            Route::get('api/graph/node/{id}', [OperationsCenterController::class, 'node'])->name('node');
        });
    });
