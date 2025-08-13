<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\TemplateController;

Route::prefix('admin')->group(function () {
    Route::get('/templates', [TemplateController::class, 'index']);
    Route::get('/templates/{template}', [TemplateController::class, 'show']);
    Route::post('/templates', [TemplateController::class, 'store']);
});

Route::get('/ping', function () {
    return response()->json(['pong' => true]);
});
