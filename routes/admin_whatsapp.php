<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WhatsAppTemplateController;

/*
|--------------------------------------------------------------------------
| Admin - WhatsApp template management
|--------------------------------------------------------------------------
| IMPORTANT:
| - The RouteServiceProvider already applies:
|   ->middleware(['web','auth','active','force_password'])
|   ->prefix('admin')
|   ->as('admin.')
| So do NOT add them again here.
*/

Route::get('whatsapp/templates',                 [WhatsAppTemplateController::class, 'index'])->name('whatsapp.templates.index');
Route::get('whatsapp/templates/create',          [WhatsAppTemplateController::class, 'create'])->name('whatsapp.templates.create');
Route::post('whatsapp/templates',                [WhatsAppTemplateController::class, 'store'])->name('whatsapp.templates.store');
Route::get('whatsapp/templates/{template}/edit', [WhatsAppTemplateController::class, 'edit'])->name('whatsapp.templates.edit');
Route::put('whatsapp/templates/{template}',      [WhatsAppTemplateController::class, 'update'])->name('whatsapp.templates.update');
Route::delete('whatsapp/templates/{template}',   [WhatsAppTemplateController::class, 'destroy'])->name('whatsapp.templates.destroy');

Route::post('whatsapp/templates/{template}/preview',   [WhatsAppTemplateController::class, 'preview'])->name('whatsapp.templates.preview');
Route::post('whatsapp/templates/{template}/test-send', [WhatsAppTemplateController::class, 'testSend'])->name('whatsapp.templates.test_send');
