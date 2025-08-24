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
use App\Http\Controllers\PasswordForceController;

/*
|--------------------------------------------------------------------------
| Force Password Change (accessible after login but BEFORE admin area)
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'active'])->group(function () {
    Route::get('password/force',  [PasswordForceController::class, 'edit'])->name('password.force.edit');
    Route::post('password/force', [PasswordForceController::class, 'update'])->name('password.force.update');
});

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
| 'active' + 'force_password' protect the admin area.
*/
Route::middleware(['web', 'auth', 'active', 'force_password'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {

    // Constrain common route parameters (prevents 'archived' being swallowed by {booking})
    Route::pattern('booking', '[0-9]+');
    Route::pattern('client', '[0-9]+');
    Route::pattern('job', '[0-9]+');
    Route::pattern('opportunity', '[0-9]+');
    Route::pattern('invoice', '[0-9]+');
    Route::pattern('user', '[0-9]+');
    Route::pattern('garage', '[0-9]+');

    // 🔷 Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 🔷 Calendar (full view + JSON feed)
    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('calendar/events', [CalendarController::class, 'events'])->name('calendar.events'); // ← add this

    // 🔷 Profile
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // 🔷 Clients (custom routes BEFORE resource)
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

    // 🔷 Opportunities (custom BEFORE resource)
    Route::get('opportunities/archived', [OpportunityController::class, 'archived'])->name('opportunities.archived');
    Route::put('opportunities/{opportunity}/restore', [OpportunityController::class, 'restore'])->name('opportunities.restore');
    Route::resource('opportunities', OpportunityController::class);

    // 🔷 Bookings (custom BEFORE resource to avoid 404/swallowing)
    Route::get('bookings/archived', [BookingController::class, 'archived'])->name('bookings.archived');
    Route::put('bookings/{booking}/archive', [BookingController::class, 'archive'])->name('bookings.archive');
    Route::put('bookings/{booking}/restore', [BookingController::class, 'restore'])->name('bookings.restore');
    Route::resource('bookings', BookingController::class);

    // 🔷 Jobs (custom BEFORE resource)
    Route::get('jobs/archived', [JobController::class, 'archived'])->name('jobs.archived');
    Route::post('jobs/{job}/archive', [JobController::class, 'archive'])->name('jobs.archive');
    Route::post('jobs/{job}/restore', [JobController::class, 'restore'])->name('jobs.restore');
    Route::resource('jobs', JobController::class);

    // 🔷 AJAX helper (Jobs by Client for invoice create/edit)
    Route::get('ajax/jobs-by-client/{client}', [InvoiceController::class, 'jobsByClient'])->name('ajax.jobs-by-client');

    // 🔷 Invoices 
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::get('invoices/{invoice}/view', [InvoiceController::class, 'view'])->name('invoices.view');

    // 🔷 Communication Logs
    Route::resource('communications', CommunicationController::class)->except(['show']);

    // 🔷 Users
    Route::resource('users', UserController::class);
    Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
    Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');

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
