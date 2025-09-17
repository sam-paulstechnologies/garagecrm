<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Automation\SendTestAutomationController;

/*
|--------------------------------------------------------------------------
| WhatsApp + Automation Routes
|--------------------------------------------------------------------------
| Public webhook endpoints (none here now) + internal automation triggers.
| Inbound WhatsApp (Twilio) is registered in routes/web.php under /webhooks/twilio/whatsapp
|--------------------------------------------------------------------------
*/

// ✅ Quick manual automation trigger (secure with token/middleware later if needed)
Route::post('/automation/test-fire', [SendTestAutomationController::class, 'handle'])
    ->name('automation.test-fire');

// Optional: simple ping to confirm this file is loaded
Route::get('/whatsapp/ping', fn () => 'whatsapp: pong')->name('whatsapp.ping');
