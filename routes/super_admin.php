<?php

use App\Http\Controllers\SuperAdmin\AuditController;
use App\Http\Controllers\SuperAdmin\CommercialMetricsController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\GarageController;
use App\Http\Controllers\SuperAdmin\LogController;
use App\Http\Controllers\SuperAdmin\MessagingConnectionController;
use App\Http\Controllers\SuperAdmin\OperationsCenterController;
use App\Http\Controllers\SuperAdmin\PlatformUserController;
use App\Http\Controllers\SuperAdmin\QuickScanController;
use App\Http\Controllers\SuperAdmin\SystemHealthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active', 'force_password', 'role:super_admin'])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('commercial', [CommercialMetricsController::class, 'index'])->name('commercial.index');
        Route::patch('commercial/launch-offer', [CommercialMetricsController::class, 'updateLaunchOffer'])
            ->middleware('security.step-up')
            ->name('commercial.launch-offer.update');

        Route::get('garages', [GarageController::class, 'index'])->name('garages.index');
        Route::get('garages/{garage}', [GarageController::class, 'show'])->name('garages.show');
        Route::patch('garages/{garage}', [GarageController::class, 'update'])->middleware('security.step-up')->name('garages.update');
        Route::post('garages/{garage}/activate', [GarageController::class, 'activate'])->middleware('security.step-up')->name('garages.activate');
        Route::post('garages/{garage}/suspend', [GarageController::class, 'suspend'])->middleware('security.step-up')->name('garages.suspend');
        Route::get('garages/{garage}/users', [GarageController::class, 'users'])->name('garages.users');
        Route::get('garages/{garage}/modules', [GarageController::class, 'modules'])->name('garages.modules');
        Route::patch('garages/{garage}/modules', [GarageController::class, 'updateModule'])->middleware('security.step-up')->name('garages.modules.update');
        Route::get('garages/{garage}/channels', [GarageController::class, 'channels'])->name('garages.channels');

        Route::post('platform-users', [PlatformUserController::class, 'store'])->middleware('security.step-up')->name('platform-users.store');
        Route::patch('platform-users/{platformUser}', [PlatformUserController::class, 'update'])->middleware('security.step-up')->name('platform-users.update');

        Route::get('logs/messages', [LogController::class, 'messages'])->name('logs.messages');
        Route::get('logs/leads', [LogController::class, 'leads'])->name('logs.leads');
        Route::get('system/health', SystemHealthController::class)->name('system.health');
        Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
        Route::get('messaging-connections', [MessagingConnectionController::class, 'index'])->name('messaging-connections.index');
        Route::get('messaging-connections/{messagingConnection}', [MessagingConnectionController::class, 'show'])->name('messaging-connections.show');
        Route::post('messaging-connections/{messagingConnection}/retry', [MessagingConnectionController::class, 'retry'])
            ->middleware(['security.step-up', 'throttle:3,1'])
            ->name('messaging-connections.retry');

        Route::get('quick-scans', [QuickScanController::class, 'index'])->name('quick-scans.index');
        Route::get('quick-scans/create', [QuickScanController::class, 'create'])->name('quick-scans.create');
        Route::post('quick-scans', [QuickScanController::class, 'store'])
            ->middleware(['security.step-up', 'throttle:10,1'])->name('quick-scans.store');
        Route::post('quick-scans/synthetic', [QuickScanController::class, 'synthetic'])
            ->middleware(['security.step-up', 'throttle:2,1'])->name('quick-scans.synthetic');
        Route::get('quick-scans/{quickScan}', [QuickScanController::class, 'show'])->name('quick-scans.show');
        Route::get('quick-scans/{quickScan}/qr', [QuickScanController::class, 'qr'])->name('quick-scans.qr');
        Route::patch('quick-scans/{quickScan}/follow-up', [QuickScanController::class, 'followUp'])
            ->middleware('security.step-up')->name('quick-scans.follow-up');
        Route::post('quick-scans/{quickScan}/regenerate', [QuickScanController::class, 'regenerate'])
            ->middleware('security.step-up')->name('quick-scans.regenerate');
        Route::post('quick-scans/{quickScan}/revoke', [QuickScanController::class, 'revoke'])
            ->middleware('security.step-up')->name('quick-scans.revoke');
        Route::delete('quick-scans/{quickScan}/customer-data', [QuickScanController::class, 'purge'])
            ->middleware('security.step-up')->name('quick-scans.purge');

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
