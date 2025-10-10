<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WhatsAppTemplateController;
use App\Http\Controllers\Admin\WhatsAppCampaignController;
use App\Http\Controllers\Admin\CommunicationLogController;

/*
|--------------------------------------------------------------------------
| Admin – WhatsApp Routes
|--------------------------------------------------------------------------
| Keep all WhatsApp-related admin routes isolated here for modularity.
| Expected to be loaded by RouteServiceProvider within the 'web' group.
*/

Route::prefix('admin')->middleware(['web', 'auth'])->as('admin.')->group(function () {

    // ---------------------------
    // WhatsApp: Templates & Campaigns
    // ---------------------------
    Route::prefix('whatsapp')->as('whatsapp.')->group(function () {

        /*
        |---------------------------
        | Templates
        |---------------------------
        */
        Route::get('templates',                 [WhatsAppTemplateController::class, 'index'])->name('templates.index');
        Route::get('templates/create',          [WhatsAppTemplateController::class, 'create'])->name('templates.create');
        Route::post('templates',                [WhatsAppTemplateController::class, 'store'])->name('templates.store');
        Route::get('templates/{template}/edit', [WhatsAppTemplateController::class, 'edit'])->name('templates.edit');
        Route::put('templates/{template}',      [WhatsAppTemplateController::class, 'update'])->name('templates.update');
        Route::delete('templates/{template}',   [WhatsAppTemplateController::class, 'destroy'])->name('templates.destroy');

        // Extras
        Route::post('templates/{template}/preview',   [WhatsAppTemplateController::class, 'preview'])->name('templates.preview');
        Route::post('templates/{template}/test-send', [WhatsAppTemplateController::class, 'testSend'])->name('templates.test_send');

        /*
        |---------------------------
        | Campaigns
        |---------------------------
        */
        Route::get('campaigns',                   [WhatsAppCampaignController::class, 'index'])->name('campaigns.index');
        Route::get('campaigns/create',            [WhatsAppCampaignController::class, 'create'])->name('campaigns.create');
        Route::post('campaigns',                  [WhatsAppCampaignController::class, 'store'])->name('campaigns.store');
        Route::get('campaigns/{campaign}/edit',   [WhatsAppCampaignController::class, 'edit'])->name('campaigns.edit');
        Route::put('campaigns/{campaign}',        [WhatsAppCampaignController::class, 'update'])->name('campaigns.update');
        Route::delete('campaigns/{campaign}',     [WhatsAppCampaignController::class, 'destroy'])->name('campaigns.destroy');

        // Common campaign actions
        Route::post('campaigns/{campaign}/send-now', [WhatsAppCampaignController::class, 'sendNow'])->name('campaigns.send_now');
        Route::post('campaigns/{campaign}/schedule', [WhatsAppCampaignController::class, 'schedule'])->name('campaigns.schedule');
        Route::post('campaigns/{campaign}/pause',    [WhatsAppCampaignController::class, 'pause'])->name('campaigns.pause');
        Route::post('campaigns/{campaign}/resume',   [WhatsAppCampaignController::class, 'resume'])->name('campaigns.resume');
    });

    // ---------------------------
    // Communication Logs (kept outside /whatsapp to live at /admin/communication/logs)
    // ---------------------------
    Route::get('communication/logs', [CommunicationLogController::class, 'index'])
        ->name('communication.logs');
});
