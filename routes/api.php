<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\Webhook\WhatsAppWebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes are stateless. CSRF is not applied to API routes.
*/

/** Admin template endpoints (API) */
Route::prefix('admin')->name('api.admin.')->group(function () {
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/{template}', [TemplateController::class, 'show'])->name('templates.show');
    Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
});

/** Simple health check */
Route::get('/ping', fn () => response()->json(['pong' => true]))->name('api.ping');

/** WhatsApp Webhooks (verification + receive) */
Route::get('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'verify'])->name('webhooks.whatsapp.verify');
Route::post('/webhooks/whatsapp', [WhatsAppWebhookController::class, 'receive'])->name('webhooks.whatsapp.receive');
