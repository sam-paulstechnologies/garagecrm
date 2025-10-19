<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All routes here use the "api" middleware stack.
| Tenant context is inferred via auth:sanctum token or authenticated user.
| Only secured / outbound operations go here.
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Api\WhatsAppTemplateApiController;
use App\Http\Controllers\Api\WhatsAppCampaignApiController;
use App\Http\Controllers\Api\WhatsAppMessageApiController;
use App\Http\Controllers\Api\WhatsAppSettingApiController;

Route::middleware(['auth:sanctum'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Templates
    |--------------------------------------------------------------------------
    */
    Route::prefix('whatsapp/templates')->group(function () {
        Route::get('/',             [WhatsAppTemplateApiController::class, 'index'])->name('api.whatsapp.templates.index');
        Route::post('/',            [WhatsAppTemplateApiController::class, 'store'])->name('api.whatsapp.templates.store');
        Route::get('/{id}',         [WhatsAppTemplateApiController::class, 'show'])->name('api.whatsapp.templates.show');
        Route::put('/{id}',         [WhatsAppTemplateApiController::class, 'update'])->name('api.whatsapp.templates.update');
        Route::delete('/{id}',      [WhatsAppTemplateApiController::class, 'destroy'])->name('api.whatsapp.templates.destroy');
        Route::post('/{id}/preview',[WhatsAppTemplateApiController::class, 'preview'])->name('api.whatsapp.templates.preview');
    });


    /*
    |--------------------------------------------------------------------------
    | WhatsApp Campaigns
    |--------------------------------------------------------------------------
    */
    Route::prefix('whatsapp/campaigns')->group(function () {
        Route::get('/',           [WhatsAppCampaignApiController::class, 'index'])->name('api.whatsapp.campaigns.index');
        Route::post('/',          [WhatsAppCampaignApiController::class, 'store'])->name('api.whatsapp.campaigns.store');
        Route::get('/{id}',       [WhatsAppCampaignApiController::class, 'show'])->name('api.whatsapp.campaigns.show');
        Route::put('/{id}',       [WhatsAppCampaignApiController::class, 'update'])->name('api.whatsapp.campaigns.update');
        Route::delete('/{id}',    [WhatsAppCampaignApiController::class, 'destroy'])->name('api.whatsapp.campaigns.destroy');
        Route::post('/{id}/send', [WhatsAppCampaignApiController::class, 'sendNow'])->name('api.whatsapp.campaigns.send');
    });


    /*
    |--------------------------------------------------------------------------
    | WhatsApp Messages (Logs & Retries)
    |--------------------------------------------------------------------------
    */
    Route::prefix('whatsapp/messages')->group(function () {
        Route::get('/',           [WhatsAppMessageApiController::class, 'index'])->name('api.whatsapp.messages.index');
        Route::get('/{id}',       [WhatsAppMessageApiController::class, 'show'])->name('api.whatsapp.messages.show');
        Route::post('/{id}/retry',[WhatsAppMessageApiController::class, 'retry'])->name('api.whatsapp.messages.retry');
    });


    /*
    |--------------------------------------------------------------------------
    | WhatsApp Settings (per tenant)
    |--------------------------------------------------------------------------
    */
    Route::prefix('whatsapp/settings')->group(function () {
        Route::get('/',  [WhatsAppSettingApiController::class, 'show'])->name('api.whatsapp.settings.show');
        Route::post('/', [WhatsAppSettingApiController::class, 'update'])->name('api.whatsapp.settings.update');
    });

});
