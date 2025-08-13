<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\ClientController;
use App\Http\Controllers\Tenant\LeadManagementController;
use App\Http\Controllers\Tenant\BookingController;
use App\Http\Controllers\Tenant\JobController;
use App\Http\Controllers\Tenant\JobCardController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Tenant\CommunicationController;

Route::middleware(['auth', 'role:tenant'])->prefix('tenant')->name('tenant.')->group(function () {
    Route::resource('clients', ClientController::class);
    Route::resource('leads', LeadManagementController::class);

    Route::get('calendar', [BookingController::class, 'calendar'])->name('calendar');
    Route::resource('bookings', BookingController::class);

    Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');

    Route::resource('jobcards', JobCardController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy', 'show']);

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::resource('invoices', InvoiceController::class)->except(['index']);

    Route::get('communications/create', [CommunicationController::class, 'create'])->name('communications.create');
    Route::post('communications/send', [CommunicationController::class, 'send'])->name('communications.send');
    Route::resource('communications', CommunicationController::class)->except(['create']);
});
