<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TemplateController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes are stateless. CSRF is not applied to API routes.
| Inbound WhatsApp (Twilio) webhooks are defined in routes/web.php.
|--------------------------------------------------------------------------
*/

/** Admin template endpoints (API) */
Route::prefix('admin')->name('api.admin.')->group(function () {
    Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('/templates/{template}', [TemplateController::class, 'show'])->name('templates.show');
    Route::post('/templates', [TemplateController::class, 'store'])->name('templates.store');
});

/** Simple health check */
Route::get('/ping', fn () => response()->json(['pong' => true]))->name('api.ping');
