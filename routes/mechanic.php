<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Mechanic\JobCardController;
use App\Http\Controllers\Mechanic\BookingController;
use App\Http\Controllers\Mechanic\LeadController;
use App\Http\Controllers\Mechanic\ProfileController;

Route::middleware(['auth', 'role:mechanic'])->prefix('mechanic')->name('mechanic.')->group(function () {
    Route::get('job-cards', [JobCardController::class, 'index'])->name('job-cards.index');
    Route::get('job-cards/{jobCard}', [JobCardController::class, 'show'])->name('job-cards.show');

    Route::get('bookings', [BookingController::class, 'index'])->name('bookings.index');
    Route::get('bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');

    Route::get('leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('leads/{lead}', [LeadController::class, 'show'])->name('leads.show');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
});
