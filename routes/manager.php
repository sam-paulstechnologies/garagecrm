<?php

use App\Http\Controllers\Admin\ManagerController;
use App\Http\Controllers\Manager\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active', 'force_password', 'role:manager'])
    ->prefix('manager')
    ->name('manager.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | Escalations
        |--------------------------------------------------------------------------
        */
        Route::get('escalations', [ManagerController::class, 'dashboard'])
            ->name('escalations');

        /*
        |--------------------------------------------------------------------------
        | Conversation
        |--------------------------------------------------------------------------
        */
        Route::get('conversation/{lead}', [ManagerController::class, 'conversation'])
            ->name('conversation');

        Route::post('conversation/{lead}/reply', [ManagerController::class, 'reply'])
            ->name('conversation.reply');

        Route::post('conversation/{lead}/resume', [ManagerController::class, 'resumeBot'])
            ->name('conversation.resume');

        /*
        |--------------------------------------------------------------------------
        | Bookings
        |--------------------------------------------------------------------------
        */
        Route::get('bookings', [ManagerController::class, 'bookings'])
            ->name('bookings.index');

        /*
        |--------------------------------------------------------------------------
        | Placeholders
        |--------------------------------------------------------------------------
        */
        Route::view('clients', 'manager.placeholder')
            ->name('clients.index');

        Route::view('leads', 'manager.placeholder')
            ->name('leads.index');

        Route::view('jobs', 'manager.placeholder')
            ->name('jobs.index');

        Route::view('invoices', 'manager.placeholder')
            ->name('invoices.index');

        Route::view('team', 'manager.placeholder')
            ->name('team.index');
    });