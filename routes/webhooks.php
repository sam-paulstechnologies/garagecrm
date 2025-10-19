<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhooks\TwilioWhatsAppWebhookController;
use App\Http\Controllers\Webhooks\MetaWhatsAppWebhookController;

/*
|--------------------------------------------------------------------------
| Public Webhooks (stateless - API middleware)
|--------------------------------------------------------------------------
| Accept GET for provider console checks; POST for real payloads.
*/

// ---------- Twilio WhatsApp ----------
Route::match(['GET','POST'], 'webhooks/twilio/whatsapp', [TwilioWhatsAppWebhookController::class, 'handle'])
    ->name('webhooks.twilio.whatsapp')
    ->middleware('throttle:120,1');

Route::match(['GET','POST'], 'webhooks/twilio/whatsapp/status', [TwilioWhatsAppWebhookController::class, 'status'])
    ->name('webhooks.twilio.whatsapp.status')
    ->middleware('throttle:240,1');

// ---------- Meta WhatsApp ----------
Route::get('webhooks/meta/whatsapp',  [MetaWhatsAppWebhookController::class, 'verify']);
Route::post('webhooks/meta/whatsapp', [MetaWhatsAppWebhookController::class, 'handle'])
    ->name('whatsapp.webhook.meta')
    ->middleware('throttle:120,1');
