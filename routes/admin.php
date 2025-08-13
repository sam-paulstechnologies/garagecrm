<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    AjaxController,
    BookingController,
    ClientController,
    CommunicationController,
    CompanyController,
    DashboardController,
    FileController,
    GarageController,
    InvoiceController,
    JobController,
    LeadController,
    OpportunityController,
    PlanController,
    SettingsController,
    UserController,
    VehicleController,
    CalendarController
};
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tenant\ClientBookingController;

Route::middleware(['web', 'auth'])->prefix('admin')->as('admin.')->group(function () {

    // 🔷 Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 🔷 Calendar
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');

    // 🔷 Profile
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 🔷 Clients (Custom routes must come before the resource route!)
    Route::get('clients/archived', [ClientController::class, 'archived'])->name('clients.archived');
    Route::post('clients/{client}/archive', [ClientController::class, 'archive'])->name('clients.archive');
    Route::post('clients/{client}/restore', [ClientController::class, 'restore'])->name('clients.restore');
    Route::get('clients/{client}/bookings', [ClientBookingController::class, 'index'])->name('clients.bookings');

    // 🔷 Client Bulk Import
    Route::get('clients/import', [ClientController::class, 'importForm'])->name('clients.import.form');
    Route::post('clients/import', [ClientController::class, 'import'])->name('clients.import');

    // 🔷 Clients
    Route::resource('clients', ClientController::class);

    // 🔷 Client Files
    Route::prefix('clients/{client}/files')->name('clients.files.')->group(function () {
        Route::get('/', [FileController::class, 'index'])->name('index');
        Route::get('/create', [FileController::class, 'create'])->name('create');
        Route::post('/', [FileController::class, 'store'])->name('store');
        Route::delete('/{file}', [FileController::class, 'destroy'])->name('destroy');
    });

    // 🔷 Leads
    Route::resource('leads', LeadController::class);

    // 🔷 Opportunities
    Route::get('opportunities/archived', [OpportunityController::class, 'archived'])->name('opportunities.archived');
    Route::put('opportunities/{id}/restore', [OpportunityController::class, 'restore'])->name('opportunities.restore');
    Route::resource('opportunities', OpportunityController::class);

    // 🔷 Bookings
    Route::resource('bookings', BookingController::class);
    Route::put('bookings/{id}/archive', [BookingController::class, 'archive'])->name('bookings.archive');
    Route::put('bookings/{id}/restore', [BookingController::class, 'restore'])->name('bookings.restore');
    Route::get('bookings/archived', [BookingController::class, 'archived'])->name('bookings.archived');

    // 🔷 Jobs
    Route::resource('jobs', JobController::class);

    // 🔷 Invoices
    Route::resource('invoices', InvoiceController::class);

    // 🔷 Communication Logs
    Route::resource('communications', CommunicationController::class)->except(['show']);

    // 🔷 Users
    Route::resource('users', UserController::class);

    // 🔷 Garages
    Route::resource('garages', GarageController::class);

    // 🔷 Company Settings
    Route::get('settings/company', [SettingsController::class, 'index'])->name('settings.company.edit');
    Route::put('settings/company', [SettingsController::class, 'update'])->name('settings.company.update');

    // 🔷 Plans
    Route::resource('plans', PlanController::class)->except(['show']);

    // 🔷 AJAX for Vehicles
    Route::get('ajax/models-by-make/{makeId}', [AjaxController::class, 'modelsByMake'])->name('ajax.models-by-make');
    Route::post('ajax/find-or-create-vehicle', [VehicleController::class, 'findOrCreate'])->name('ajax.find-or-create-vehicle');

    // 🔷 Health Check
    Route::get('/example', fn () => response()->json(['message' => 'Garage CRM API is working!']));
});
 