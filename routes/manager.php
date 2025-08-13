<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Manager\ClientController;
use App\Http\Controllers\Manager\LeadController;
use App\Http\Controllers\Manager\BookingController;
use App\Http\Controllers\Manager\JobController;
use App\Http\Controllers\Manager\InvoiceController;
use App\Http\Controllers\Manager\CommunicationController;
use App\Http\Controllers\Manager\TeamController;

Route::middleware(['auth', 'role:manager'])->prefix('manager')->name('manager.')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::resource('leads', LeadController::class)->only(['index', 'show', 'edit', 'update']);
    Route::resource('bookings', BookingController::class)->only(['index', 'show', 'edit', 'update']);

    Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');
    Route::get('jobs/{job}', [JobController::class, 'show'])->name('jobs.show');

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    Route::get('team', [TeamController::class, 'index'])->name('team.index');
    Route::get('team/{user}', [TeamController::class, 'show'])->name('team.show');

    Route::get('communications/create', [CommunicationController::class, 'create'])->name('communications.create');
    Route::post('communications/send', [CommunicationController::class, 'send'])->name('communications.send');
});
