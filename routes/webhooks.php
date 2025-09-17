<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Webhooks\TwilioWhatsAppWebhookController;
use App\Http\Controllers\Webhooks\EmailInboundWebhookController;

Route::post('/webhooks/twilio/whatsapp', [TwilioWhatsAppWebhookController::class, 'handle'])
    ->name('webhooks.twilio.whatsapp');

Route::post('/webhooks/email/inbound', [EmailInboundWebhookController::class, 'handle'])
    ->name('webhooks.email.inbound');
