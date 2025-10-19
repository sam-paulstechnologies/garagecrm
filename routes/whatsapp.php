<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\WhatsAppTemplateController;
use App\Http\Controllers\Admin\WhatsAppCampaignController;
use App\Http\Controllers\Admin\WhatsAppMappingController;
use App\Http\Controllers\Admin\WhatsAppMessageController;
use App\Http\Controllers\Admin\WhatsAppSettingController;
use App\Http\Controllers\Admin\WhatsAppPerformanceController;
use App\Http\Controllers\Admin\MessageLogController;

use App\Jobs\SendWhatsAppFromTemplate;
use App\Models\Client\Lead;

// Optional admin dashboard (will live at /admin/dashboard)
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// /admin/whatsapp/*
Route::prefix('whatsapp')->as('whatsapp.')->group(function () {

    // Templates
    Route::get('templates',                 [WhatsAppTemplateController::class, 'index'])->name('templates.index');
    Route::get('templates/create',          [WhatsAppTemplateController::class, 'create'])->name('templates.create');
    Route::post('templates',                [WhatsAppTemplateController::class, 'store'])->name('templates.store');
    Route::get('templates/{template}',      [WhatsAppTemplateController::class, 'show'])->name('templates.show');
    Route::get('templates/{template}/edit', [WhatsAppTemplateController::class, 'edit'])->name('templates.edit');
    Route::put('templates/{template}',      [WhatsAppTemplateController::class, 'update'])->name('templates.update');
    Route::delete('templates/{template}',   [WhatsAppTemplateController::class, 'destroy'])->name('templates.destroy');

    // Template extras
    Route::post('templates/{template}/preview',   [WhatsAppTemplateController::class, 'preview'])->name('templates.preview');
    Route::post('templates/{template}/test-send', [WhatsAppTemplateController::class, 'testSend'])->name('templates.test_send');

    // Campaigns
    Route::get('campaigns',                      [WhatsAppCampaignController::class, 'index'])->name('campaigns.index');
    Route::get('campaigns/create',               [WhatsAppCampaignController::class, 'create'])->name('campaigns.create');
    Route::post('campaigns',                     [WhatsAppCampaignController::class, 'store'])->name('campaigns.store');
    Route::get('campaigns/{campaign}/edit',      [WhatsAppCampaignController::class, 'edit'])->name('campaigns.edit');
    Route::put('campaigns/{campaign}',           [WhatsAppCampaignController::class, 'update'])->name('campaigns.update');
    Route::delete('campaigns/{campaign}',        [WhatsAppCampaignController::class, 'destroy'])->name('campaigns.destroy');
    Route::post('campaigns/{campaign}/send-now', [WhatsAppCampaignController::class, 'sendNow'])->name('campaigns.send_now');
    Route::post('campaigns/{campaign}/schedule', [WhatsAppCampaignController::class, 'schedule'])->name('campaigns.schedule');
    Route::post('campaigns/{campaign}/pause',    [WhatsAppCampaignController::class, 'pause'])->name('campaigns.pause');
    Route::post('campaigns/{campaign}/resume',   [WhatsAppCampaignController::class, 'resume'])->name('campaigns.resume');

    // Trigger → Template mappings
    Route::get('mappings',                   [WhatsAppMappingController::class, 'index'])->name('mappings.index');
    Route::post('mappings',                  [WhatsAppMappingController::class, 'store'])->name('mappings.store');
    Route::put('mappings/{mapping}',         [WhatsAppMappingController::class, 'update'])->name('mappings.update');
    Route::post('mappings/{mapping}/toggle', [WhatsAppMappingController::class, 'toggle'])->name('mappings.toggle');

    // Messages UI
    Route::get('messages',                   [WhatsAppMessageController::class, 'index'])->name('messages.index');
    Route::get('messages/{message}',         [WhatsAppMessageController::class, 'show'])->name('messages.show');
    Route::post('messages/{message}/retry',  [WhatsAppMessageController::class, 'retry'])->name('messages.retry');

    // Logs viewer
    Route::get('logs',                    [MessageLogController::class, 'index'])->name('logs.index');
    Route::get('logs/{log}',              [MessageLogController::class, 'show'])->name('logs.show');
    Route::get('logs-export/csv',         [MessageLogController::class, 'exportCsv'])->name('logs.export.csv');

    // ✅ Settings (fixes: Route [admin.whatsapp.settings.edit] not defined)
    Route::get('settings', [WhatsAppSettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [WhatsAppSettingController::class, 'update'])->name('settings.update');

    // ✅ Performance (fixes: Route [admin.whatsapp.performance.index] not defined)
    Route::get('performance', [WhatsAppPerformanceController::class, 'index'])
        ->name('performance.index');

    // Dev smoke
    Route::get('dev/wa-smoke', function (\Illuminate\Http\Request $r) {
        $user = auth()->user();
        $companyId = (int) $user->company_id;

        $lead = null;
        if ($id = $r->query('lead')) {
            $lead = Lead::where('company_id', $companyId)->find((int)$id);
        }
        if (!$lead && ($phone = $r->query('phone'))) {
            $norm = preg_replace('/\D+/', '', (string)$phone);
            $lead = Lead::where('company_id', $companyId)->where('phone_norm', $norm)->latest('id')->first();
        }
        if (!$lead) {
            $lead = Lead::where('company_id', $companyId)->whereNotNull('phone')->latest('id')->first();
        }
        if (!$lead) {
            return response('No testable lead found. Create a lead with a valid E.164 phone first.', 400);
        }

        SendWhatsAppFromTemplate::dispatch(
            companyId:    $companyId,
            leadId:       $lead->id,
            toNumberE164: $lead->phone,
            templateName: 'lead_welcome',
            placeholders: [$lead->name ?: 'there'],
            links:        [],
            context:      []
        );

        return "✅ Dispatched WA for lead #{$lead->id} ({$lead->name}). Check phone {$lead->phone}.";
    })->name('dev.wa_smoke');
});
