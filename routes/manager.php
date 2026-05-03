<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Admin\ManagerController; // 🔥 ADD THIS

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
        | 🔥 ESCALATIONS (CORE FEATURE)
        |--------------------------------------------------------------------------
        */
        Route::get('escalations', [ManagerController::class, 'dashboard'])
            ->name('escalations');

        /*
        |--------------------------------------------------------------------------
        | 💬 Conversation
        |--------------------------------------------------------------------------
        */
        Route::get('conversation/{lead}', [ManagerController::class, 'conversation'])
            ->name('conversation');

        /*
        |--------------------------------------------------------------------------
        | 📤 Reply
        |--------------------------------------------------------------------------
        */
        Route::post('conversation/{lead}/reply', [ManagerController::class, 'reply'])
            ->name('conversation.reply');

        /*
        |--------------------------------------------------------------------------
        | 🔓 Resume Bot
        |--------------------------------------------------------------------------
        */
        Route::post('conversation/{lead}/resume', [ManagerController::class, 'resumeBot'])
            ->name('conversation.resume');

        /*
        |--------------------------------------------------------------------------
        | 📅 Bookings (REAL)
        |--------------------------------------------------------------------------
        */
        Route::get('bookings', [ManagerController::class, 'bookings'])
            ->name('bookings.index');

        /*
        |--------------------------------------------------------------------------
        | (Keep placeholders for future)
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