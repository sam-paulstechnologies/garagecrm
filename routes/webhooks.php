<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhook\TwilioWhatsAppWebhookController;

/*
|--------------------------------------------------------------------------
| Public Webhooks
|--------------------------------------------------------------------------
| These endpoints must be publicly accessible to providers like Twilio.
| Keep them minimal, verified (e.g., signing checks if you add later),
| and outside auth middleware.
*/

Route::post('webhooks/twilio/whatsapp', [TwilioWhatsAppWebhookController::class, 'handle'])
    ->name('webhooks.twilio.whatsapp');
