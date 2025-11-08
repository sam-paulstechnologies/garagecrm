<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyCsrfToken;

/** Webhooks */
use App\Http\Controllers\Webhooks\TwilioWhatsAppWebhookController;

/** Auth’d API */
use App\Http\Controllers\Api\WhatsAppTemplateApiController;
use App\Http\Controllers\Api\WhatsAppCampaignApiController;
use App\Http\Controllers\Api\WhatsAppMessageApiController;
use App\Http\Controllers\Api\WhatsAppSettingApiController;

/** Sprint-2: Booking summary + transitions */
use App\Http\Controllers\Api\BookingSummaryController;
use App\Http\Controllers\Api\BookingTransitionController;

/*
|--------------------------------------------------------------------------
| Public (no auth) API v1
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {
    // Health check
    Route::get('/health', fn () => response()->json(['ok' => true, 'ts' => now()->toISOString()]));

    // Twilio WhatsApp webhooks (no CSRF)
    Route::match(['GET','POST','HEAD'], '/webhooks/twilio/whatsapp',
        [TwilioWhatsAppWebhookController::class, 'handle']
    )->withoutMiddleware(VerifyCsrfToken::class)
     ->name('api.webhooks.twilio.whatsapp');

    Route::match(['GET','POST','HEAD'], '/webhooks/twilio/whatsapp/status',
        [TwilioWhatsAppWebhookController::class, 'status']
    )->withoutMiddleware(VerifyCsrfToken::class)
     ->name('api.webhooks.twilio.whatsapp.status');

    Route::get('/webhooks/twilio/ping', fn () => 'twilio-ok')
        ->withoutMiddleware(VerifyCsrfToken::class);

    // Generic OPTIONS preflight for any webhook path
    Route::options('/webhooks/{any}', fn () => response()->noContent())
        ->where('any', '.*')
        ->withoutMiddleware(VerifyCsrfToken::class);
});

/*
|--------------------------------------------------------------------------
| Authenticated API v1
| NOTE: Using fixed throttle "60,1" to avoid MissingRateLimiterException.
|       When you register a named "api" limiter, you can switch back to 'throttle:api'.
|--------------------------------------------------------------------------
*/
Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:60,1'])
    ->group(function () {

        // Current user
        Route::get('/me', \App\Http\Controllers\Api\MeController::class)->name('api.me');

        // WhatsApp Templates
        Route::prefix('whatsapp/templates')->as('api.whatsapp.templates.')->group(function () {
            Route::get('/',              [WhatsAppTemplateApiController::class, 'index'])->name('index');
            Route::post('/',             [WhatsAppTemplateApiController::class, 'store'])->name('store');
            Route::get('/{id}',          [WhatsAppTemplateApiController::class, 'show'])->name('show');
            Route::put('/{id}',          [WhatsAppTemplateApiController::class, 'update'])->name('update');
            Route::delete('/{id}',       [WhatsAppTemplateApiController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/preview', [WhatsAppTemplateApiController::class, 'preview'])->name('preview');
        });

        // WhatsApp Campaigns
        Route::prefix('whatsapp/campaigns')->as('api.whatsapp.campaigns.')->group(function () {
            Route::get('/',           [WhatsAppCampaignApiController::class, 'index'])->name('index');
            Route::post('/',          [WhatsAppCampaignApiController::class, 'store'])->name('store');
            Route::get('/{id}',       [WhatsAppCampaignApiController::class, 'show'])->name('show');
            Route::put('/{id}',       [WhatsAppCampaignApiController::class, 'update'])->name('update');
            Route::delete('/{id}',    [WhatsAppCampaignApiController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/send', [WhatsAppCampaignApiController::class, 'sendNow'])->name('send');
        });

        // WhatsApp Messages
        Route::prefix('whatsapp/messages')->as('api.whatsapp.messages.')->group(function () {
            Route::get('/',            [WhatsAppMessageApiController::class, 'index'])->name('index');
            Route::get('/{id}',        [WhatsAppMessageApiController::class, 'show'])->name('show');
            Route::post('/{id}/retry', [WhatsAppMessageApiController::class, 'retry'])->name('retry');
        });

        // WhatsApp Settings
        Route::prefix('whatsapp/settings')->as('api.whatsapp.settings.')->group(function () {
            Route::get('/',  [WhatsAppSettingApiController::class, 'show'])->name('show');
            Route::post('/', [WhatsAppSettingApiController::class, 'update'])->name('update');
        });

        // Sprint-2: Booking Summary & Transition
        Route::get('/bookings/{id}/summary',     [BookingSummaryController::class, 'show'])->name('api.bookings.summary');
        Route::post('/bookings/{id}/transition', [BookingTransitionController::class, 'store'])->name('api.bookings.transition');
    });

/*
|--------------------------------------------------------------------------
| Fallback
|--------------------------------------------------------------------------
*/
Route::fallback(function () {
    return response()->json(['message' => 'Endpoint not found.'], 404);
});
