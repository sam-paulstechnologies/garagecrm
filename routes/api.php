<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Webhooks\TwilioWhatsAppWebhookController;
use App\Http\Controllers\Webhooks\MetaWhatsAppWebhookController;
use App\Http\Controllers\Webhooks\MetaWebhookController;
use App\Http\Controllers\Webhooks\GoogleLeadWebhookController;

/*
|--------------------------------------------------------------------------
| Public API Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Api\WebsiteLeadController;

/*
|--------------------------------------------------------------------------
| Authenticated API Controllers
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Api\WhatsAppTemplateApiController;
use App\Http\Controllers\Api\WhatsAppCampaignApiController;
use App\Http\Controllers\Api\WhatsAppMessageApiController;
use App\Http\Controllers\Api\WhatsAppSettingApiController;
use App\Http\Controllers\Api\BookingSummaryController;
use App\Http\Controllers\Api\BookingTransitionController;
use App\Http\Controllers\Api\MeController;

/*
|--------------------------------------------------------------------------
| Public API v1 (Stateless)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    // Health
    Route::get('/health', fn () => response()->json([
        'ok' => true,
        'ts' => now()->toISOString(),
    ]));

    // Website Lead
    Route::post(
        '/website-leads/{token}',
        [WebsiteLeadController::class, 'store']
    )->name('api.website-leads.store');

    /*
    |--------------------------------------------------------------------------
    | GOOGLE ADS LEAD FORM WEBHOOK
    |--------------------------------------------------------------------------
    | This is for Google Ads Lead Form webhook submissions.
    |
    | Google Ads setup:
    | Webhook URL:
    | https://app.sayaraforce.com/api/v1/webhooks/google/leads
    |
    | Webhook Key:
    | lead_sources.form_token for the Google lead source.
    */
    Route::post(
        '/webhooks/google/leads',
        [GoogleLeadWebhookController::class, 'handle']
    )->name('api.webhooks.google.leads.handle');

    /*
    |--------------------------------------------------------------------------
    | META LEAD ADS WEBHOOK
    |--------------------------------------------------------------------------
    | This is only for Facebook / Instagram Lead Ads leadgen webhooks.
    | Do not mix this with Meta WhatsApp webhooks.
    */
    Route::get(
        '/webhooks/meta/leads',
        [MetaWebhookController::class, 'verify']
    )->name('api.webhooks.meta.leads.verify');

    Route::post(
        '/webhooks/meta/leads',
        [MetaWebhookController::class, 'handle']
    )->name('api.webhooks.meta.leads.handle');

    /*
    |--------------------------------------------------------------------------
    | META WHATSAPP WEBHOOK
    |--------------------------------------------------------------------------
    | This is only for WhatsApp Cloud API messages/statuses.
    */
    Route::get(
        '/webhooks/meta/whatsapp',
        [MetaWhatsAppWebhookController::class, 'verify']
    )->name('api.webhooks.meta.whatsapp.verify');

    Route::post(
        '/webhooks/meta/whatsapp',
        [MetaWhatsAppWebhookController::class, 'handle']
    )->name('api.webhooks.meta.whatsapp.handle');

    /*
    |--------------------------------------------------------------------------
    | TWILIO WHATSAPP WEBHOOKS
    |--------------------------------------------------------------------------
    */
    Route::match(['GET', 'POST', 'HEAD'],
        '/webhooks/twilio/whatsapp',
        [TwilioWhatsAppWebhookController::class, 'handle']
    )->name('api.webhooks.twilio.whatsapp');

    Route::match(['GET', 'POST', 'HEAD'],
        '/webhooks/twilio/whatsapp/status',
        [TwilioWhatsAppWebhookController::class, 'status']
    )->name('api.webhooks.twilio.whatsapp.status');

    Route::get('/webhooks/twilio/ping', fn () => 'twilio-ok');
});

/*
|--------------------------------------------------------------------------
| Authenticated API v1 (Sanctum)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:60,1'])
    ->group(function () {

        Route::get('/me', MeController::class)->name('api.me');

        Route::prefix('whatsapp/templates')
            ->as('api.whatsapp.templates.')
            ->group(function () {
                Route::get('/', [WhatsAppTemplateApiController::class, 'index'])->name('index');
                Route::post('/', [WhatsAppTemplateApiController::class, 'store'])->name('store');
                Route::get('/{id}', [WhatsAppTemplateApiController::class, 'show'])->name('show');
                Route::put('/{id}', [WhatsAppTemplateApiController::class, 'update'])->name('update');
                Route::delete('/{id}', [WhatsAppTemplateApiController::class, 'destroy'])->name('destroy');
                Route::post('/{id}/preview', [WhatsAppTemplateApiController::class, 'preview'])->name('preview');
            });

        Route::prefix('whatsapp/campaigns')
            ->as('api.whatsapp.campaigns.')
            ->group(function () {
                Route::get('/', [WhatsAppCampaignApiController::class, 'index'])->name('index');
                Route::post('/', [WhatsAppCampaignApiController::class, 'store'])->name('store');
                Route::get('/{id}', [WhatsAppCampaignApiController::class, 'show'])->name('show');
                Route::put('/{id}', [WhatsAppCampaignApiController::class, 'update'])->name('update');
                Route::delete('/{id}', [WhatsAppCampaignApiController::class, 'destroy'])->name('destroy');
                Route::post('/{id}/send', [WhatsAppCampaignApiController::class, 'sendNow'])->name('send');
            });

        Route::prefix('whatsapp/messages')
            ->as('api.whatsapp.messages.')
            ->group(function () {
                Route::get('/', [WhatsAppMessageApiController::class, 'index'])->name('index');
                Route::get('/{id}', [WhatsAppMessageApiController::class, 'show'])->name('show');
                Route::post('/{id}/retry', [WhatsAppMessageApiController::class, 'retry'])->name('retry');
            });

        Route::prefix('whatsapp/settings')
            ->as('api.whatsapp.settings.')
            ->group(function () {
                Route::get('/', [WhatsAppSettingApiController::class, 'show'])->name('show');
                Route::post('/', [WhatsAppSettingApiController::class, 'update'])->name('update');
            });

        Route::get('/bookings/{id}/summary', [BookingSummaryController::class, 'show'])
            ->name('api.bookings.summary');

        Route::post('/bookings/{id}/transition', [BookingTransitionController::class, 'store'])
            ->name('api.bookings.transition');
    });

/*
|--------------------------------------------------------------------------
| API Fallback
|--------------------------------------------------------------------------
*/
Route::fallback(fn () => response()->json([
    'message' => 'Endpoint not found.',
], 404));