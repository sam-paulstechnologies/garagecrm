<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\Api\WebsiteLeadController;
use App\Http\Controllers\Webhooks\MetaWebhookController;
use App\Http\Controllers\Webhooks\MetaWhatsAppWebhookController;
use App\Http\Controllers\Webhooks\TwilioWhatsAppWebhookController;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
| These routes are loaded from routes/web.php because API bootstrap loading
| is currently not registering routes/api.php in this project.
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')->group(function () {

    Route::get('/health', fn () => response()->json([
        'ok' => true,
        'ts' => now()->toISOString(),
    ]));

    Route::post('/website-leads/{token}', [WebsiteLeadController::class, 'store'])
        ->name('api.website-leads.store')
        ->withoutMiddleware(VerifyCsrfToken::class);

    /*
    |--------------------------------------------------------------------------
    | Meta Lead Ads Webhook
    |--------------------------------------------------------------------------
    */
    Route::get('/webhooks/meta/leads', [MetaWebhookController::class, 'verify'])
        ->name('api.webhooks.meta.leads.verify')
        ->withoutMiddleware(VerifyCsrfToken::class);

    Route::post('/webhooks/meta/leads', [MetaWebhookController::class, 'handle'])
        ->name('api.webhooks.meta.leads.handle')
        ->withoutMiddleware(VerifyCsrfToken::class);

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Cloud API Webhook
    |--------------------------------------------------------------------------
    */
    Route::get('/webhooks/meta/whatsapp', [MetaWhatsAppWebhookController::class, 'verify'])
        ->name('api.webhooks.meta.whatsapp.verify')
        ->withoutMiddleware(VerifyCsrfToken::class);

    Route::post('/webhooks/meta/whatsapp', [MetaWhatsAppWebhookController::class, 'handle'])
        ->name('api.webhooks.meta.whatsapp.handle')
        ->withoutMiddleware(VerifyCsrfToken::class);

    /*
    |--------------------------------------------------------------------------
    | Twilio WhatsApp Webhooks
    |--------------------------------------------------------------------------
    */
    Route::match(['GET', 'POST', 'HEAD'], '/webhooks/twilio/whatsapp', [TwilioWhatsAppWebhookController::class, 'handle'])
        ->name('api.webhooks.twilio.whatsapp')
        ->withoutMiddleware(VerifyCsrfToken::class);

    Route::match(['GET', 'POST', 'HEAD'], '/webhooks/twilio/whatsapp/status', [TwilioWhatsAppWebhookController::class, 'status'])
        ->name('api.webhooks.twilio.whatsapp.status')
        ->withoutMiddleware(VerifyCsrfToken::class);

    Route::get('/webhooks/twilio/ping', function () {
        abort_unless(app()->environment('local'), 404);

        return 'twilio-ok';
    });
});
