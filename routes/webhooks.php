<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhooks\TwilioWhatsAppWebhookController;

/*
|--------------------------------------------------------------------------
| Public Webhooks (Stateless)
|--------------------------------------------------------------------------
| Only Twilio lives here.
| Meta WhatsApp webhook must exist ONLY in routes/api.php
*/

// ---------- Twilio WhatsApp ----------
Route::match(['GET','POST'], 'webhooks/twilio/whatsapp', [TwilioWhatsAppWebhookController::class, 'handle'])
    ->name('webhooks.twilio.whatsapp')
    ->middleware('throttle:120,1');

Route::match(['GET','POST'], 'webhooks/twilio/whatsapp/status', [TwilioWhatsAppWebhookController::class, 'status'])
    ->name('webhooks.twilio.whatsapp.status')
    ->middleware('throttle:240,1');