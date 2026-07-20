<?php

use App\Http\Controllers\SuperAdmin\AuditController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\GarageController;
use App\Http\Controllers\SuperAdmin\LogController;
use App\Http\Controllers\SuperAdmin\SystemHealthController;
use App\Http\Controllers\SuperAdmin\Marketing\AppointmentController as MarketingAppointmentController;
use App\Http\Controllers\SuperAdmin\Marketing\CampaignController as MarketingCampaignController;
use App\Http\Controllers\SuperAdmin\Marketing\ChannelController as MarketingChannelController;
use App\Http\Controllers\SuperAdmin\Marketing\ConversationController as MarketingConversationController;
use App\Http\Controllers\SuperAdmin\Marketing\DashboardController as MarketingDashboardController;
use App\Http\Controllers\SuperAdmin\Marketing\ProspectController as MarketingProspectController;
use App\Http\Controllers\SuperAdmin\Marketing\ReportController as MarketingReportController;
use App\Http\Controllers\SuperAdmin\Marketing\SegmentController as MarketingSegmentController;
use App\Http\Controllers\SuperAdmin\Marketing\StaticPageController as MarketingStaticPageController;
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

        Route::prefix('marketing')->name('marketing.')->group(function () {
            Route::redirect('/', '/super-admin/marketing/dashboard')->name('index');
            Route::get('dashboard', MarketingDashboardController::class)->name('dashboard');

            Route::get('prospects/export', [MarketingProspectController::class, 'export'])->name('prospects.export');
            Route::resource('prospects', MarketingProspectController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);

            Route::resource('segments', MarketingSegmentController::class)->only(['index', 'store', 'show']);

            Route::resource('campaigns', MarketingCampaignController::class)->only(['index', 'create', 'store', 'show']);
            Route::post('campaigns/{campaign}/prepare', [MarketingCampaignController::class, 'prepare'])->name('campaigns.prepare');
            Route::post('campaigns/{campaign}/approve', [MarketingCampaignController::class, 'approve'])->name('campaigns.approve');
            Route::post('campaigns/{campaign}/launch', [MarketingCampaignController::class, 'launch'])->name('campaigns.launch');
            Route::post('campaigns/{campaign}/pause', [MarketingCampaignController::class, 'pause'])->name('campaigns.pause');
            Route::post('campaigns/{campaign}/stop', [MarketingCampaignController::class, 'stop'])->name('campaigns.stop');

            Route::get('conversations', [MarketingConversationController::class, 'index'])->name('conversations.index');
            Route::get('conversations/{conversation}', [MarketingConversationController::class, 'show'])->name('conversations.show');
            Route::post('conversations/{conversation}/pause-ai', [MarketingConversationController::class, 'pauseAi'])->name('conversations.pause-ai');
            Route::post('conversations/{conversation}/resume-ai', [MarketingConversationController::class, 'resumeAi'])->name('conversations.resume-ai');
            Route::post('conversations/{conversation}/takeover', [MarketingConversationController::class, 'takeover'])->name('conversations.takeover');

            Route::get('appointments', [MarketingAppointmentController::class, 'index'])->name('appointments.index');
            Route::post('appointments', [MarketingAppointmentController::class, 'store'])->name('appointments.store');

            Route::get('imports', [MarketingStaticPageController::class, 'imports'])->name('imports.index');
            Route::get('templates', [MarketingStaticPageController::class, 'templates'])->name('templates.index');
            Route::get('settings', [MarketingStaticPageController::class, 'settings'])->name('settings.index');
            Route::get('suppression-list', [MarketingStaticPageController::class, 'suppressionList'])->name('suppression-list.index');
            Route::get('reports', [MarketingReportController::class, 'index'])->name('reports.index');
            Route::get('channel', [MarketingChannelController::class, 'index'])->name('channel.index');
            Route::post('channel', [MarketingChannelController::class, 'store'])->name('channel.store');
        });
    });
