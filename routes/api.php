<?php

// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WhatsAppTemplateApiController;

Route::middleware(['auth:sanctum'])->group(function () {
  Route::get('/whatsapp/templates', [WhatsAppTemplateApiController::class, 'index']);
  Route::post('/whatsapp/templates', [WhatsAppTemplateApiController::class, 'store']);
  Route::get('/whatsapp/templates/{id}', [WhatsAppTemplateApiController::class, 'show']);
  Route::put('/whatsapp/templates/{id}', [WhatsAppTemplateApiController::class, 'update']);
  Route::delete('/whatsapp/templates/{id}', [WhatsAppTemplateApiController::class, 'destroy']);

  Route::post('/whatsapp/templates/{id}/preview', [WhatsAppTemplateApiController::class, 'preview']);
});
