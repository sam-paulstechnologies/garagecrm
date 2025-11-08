<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    AjaxController,
    BookingController,
    ClientController,
    CommunicationController,
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
    CalendarController,
    LeadImportController,
    LeadDuplicateController,
    TemplateController,
    AiSettingController,        // AI Control Center (edit/update)
    BusinessProfileController,  // Business Profile & Escalation
    AiPolicyController,         // AI Policy (intent matrix + policy reply)
    AiInsightsController,       // AI Insights dashboard
    ChatController              // Unified Chat UI
};
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tenant\ClientBookingController;

/*
|--------------------------------------------------------------------------
| This file is auto-wrapped by RouteServiceProvider with:
|   middleware(['web','auth','active','force_password'])
|   prefix('admin')->as('admin.')
| DO NOT add those again here.
|--------------------------------------------------------------------------
*/

/** Common parameter constraints */
Route::pattern('booking', '[0-9]+');
Route::pattern('client',  '[0-9]+');
Route::pattern('job',     '[0-9]+');
Route::pattern('opportunity', '[0-9]+');
Route::pattern('invoice', '[0-9]+');
Route::pattern('user',    '[0-9]+');
Route::pattern('garage',  '[0-9]+');
Route::pattern('vehicle', '[0-9]+');
Route::pattern('communication', '[0-9]+');
Route::pattern('lead',    '[0-9]+');
Route::pattern('chat',    '[0-9]+');

/** Dashboard */
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

/** Calendar */
Route::get('calendar',        [CalendarController::class, 'index'])->name('calendar.index');
Route::get('calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

/** Profile (self) */
Route::get('profile',    [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('profile',  [ProfileController::class, 'update'])->name('profile.update');
Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

/** Clients (customs BEFORE resource) */
Route::get('clients/archived',               [ClientController::class, 'archived'])->name('clients.archived');
Route::post('clients/{client}/archive',      [ClientController::class, 'archive'])->name('clients.archive');
Route::post('clients/{client}/restore',      [ClientController::class, 'restore'])->name('clients.restore');
Route::get('clients/{client}/bookings',      [ClientBookingController::class, 'index'])->name('clients.bookings');
Route::post('clients/{client}/notes',        [ClientController::class, 'storeNote'])->name('clients.notes.store');
Route::get('clients/{client}/notes',         [ClientController::class, 'notesIndex'])->name('clients.notes.index');

/** Client Bulk Import */
Route::get('clients/import',  [ClientController::class, 'importForm'])->name('clients.import.form');
Route::post('clients/import', [ClientController::class, 'import'])->name('clients.import');

/** Clients */
Route::resource('clients', ClientController::class);

/** Client Files */
Route::prefix('clients/{client}/files')->name('clients.files.')->group(function () {
    Route::get('/',          [FileController::class, 'index'])->name('index');
    Route::get('/create',    [FileController::class, 'create'])->name('create');
    Route::post('/',         [FileController::class, 'store'])->name('store');
    Route::delete('/{file}', [FileController::class, 'destroy'])->name('destroy');
});

/** Leads — quick actions + Co-Pilot (BEFORE resource) */
Route::patch('leads/{lead}/toggle-hot',     [LeadController::class, 'toggleHot'])->name('leads.toggleHot');
Route::patch('leads/{lead}/assign',         [LeadController::class, 'assign'])->name('leads.assign');
Route::post('leads/{lead}/convert',         [LeadController::class, 'convert'])->name('leads.convert');
Route::patch('leads/{lead}/touch',          [LeadController::class, 'touchContacted'])->name('leads.touch');

Route::get( 'leads/{lead}/copilot/meta',            [LeadController::class, 'copilotMeta'])->name('leads.copilot.meta');
Route::post('leads/{lead}/copilot/suggest-reply',   [LeadController::class, 'copilotSuggestReply'])->name('leads.copilot.suggest');
Route::post('leads/{lead}/copilot/quick-booking',   [LeadController::class, 'copilotQuickBooking'])->name('leads.copilot.quick-booking');
Route::post('leads/{lead}/copilot/followup',        [LeadController::class, 'copilotScheduleFollowup'])->name('leads.copilot.followup');
Route::post('leads/{lead}/copilot/send-template',   [LeadController::class, 'copilotSendTemplate'])->name('leads.copilot.send-template');

/** Leads (resource LAST so it doesn't swallow customs) */
Route::resource('leads', LeadController::class);

/** Opportunities (customs BEFORE resource) */
Route::get('opportunities/archived',              [OpportunityController::class, 'archived'])->name('opportunities.archived');
Route::put('opportunities/{opportunity}/restore', [OpportunityController::class, 'restore'])->name('opportunities.restore');
Route::resource('opportunities', OpportunityController::class);

/** Bookings (customs BEFORE resource) */
Route::get('bookings/archived',          [BookingController::class, 'archived'])->name('bookings.archived');
Route::put('bookings/{booking}/archive', [BookingController::class, 'archive'])->name('bookings.archive');
Route::put('bookings/{booking}/restore', [BookingController::class, 'restore'])->name('bookings.restore');
Route::resource('bookings', BookingController::class);

/** Jobs (customs BEFORE resource) */
Route::get('jobs/archived',           [JobController::class, 'archived'])->name('jobs.archived');
Route::post('jobs/{job}/archive',     [JobController::class, 'archive'])->name('jobs.archive');
Route::post('jobs/{job}/restore',     [JobController::class, 'restore'])->name('jobs.restore');
Route::post('jobs/{job}/card/upload', [JobController::class, 'uploadCard'])->name('jobs.card.upload');
Route::resource('jobs', JobController::class);

/** AJAX helper (Jobs by Client for invoice create/edit) */
Route::get('ajax/jobs-by-client/{client}', [InvoiceController::class, 'jobsByClient'])->name('ajax.jobs-by-client');

/** Invoices */
Route::resource('invoices', InvoiceController::class);
Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
Route::get('invoices/{invoice}/view',     [InvoiceController::class, 'view'])->name('invoices.view');

/** Invoice quick uploads + make-primary */
Route::post('jobs/{job}/invoices/upload',       [InvoiceController::class, 'uploadForJob'])->name('jobs.invoices.upload');
Route::post('clients/{client}/invoices/upload', [InvoiceController::class, 'uploadForClient'])->name('clients.invoices.upload');
Route::post('invoices/{invoice}/primary',       [InvoiceController::class, 'makePrimary'])->name('invoices.primary');

/** Communication Logs */
Route::resource('communications', CommunicationController::class)->except(['show']);
Route::get('communications/followups',                  [CommunicationController::class, 'followups'])->name('communications.followups');
Route::patch('communications/{communication}/complete', [CommunicationController::class, 'complete'])->name('communications.complete');
Route::get('communications/export/csv',                 [CommunicationController::class, 'exportCsv'])->name('communications.export.csv');

/** Back-compat alias */
Route::get('communication/logs', fn () => redirect()->route('admin.communications.index'))
    ->name('communication.logs');

/** Client-scoped Communications */
Route::get('clients/{client}/communications',  [CommunicationController::class, 'indexForClient'])->name('clients.communications.index');
Route::post('clients/{client}/communications', [CommunicationController::class, 'storeForClient'])->name('clients.communications.store');

/** AJAX badges/widgets */
Route::get('ajax/communications/due-count', [CommunicationController::class, 'dueCount'])->name('ajax.communications.due-count');

/** Marketing module */
Route::prefix('marketing')->name('marketing.')->group(function () {
    Route::resource('campaigns', \App\Http\Controllers\Admin\Marketing\CampaignController::class);
    Route::resource('triggers',  \App\Http\Controllers\Admin\Marketing\TriggerController::class)->except(['show']);
    Route::post('campaigns/{campaign}/activate', [\App\Http\Controllers\Admin\Marketing\CampaignController::class,'activate'])->name('campaigns.activate');
    Route::post('campaigns/{campaign}/pause',    [\App\Http\Controllers\Admin\Marketing\CampaignController::class,'pause'])->name('campaigns.pause');
});

/** Users */
Route::resource('users', UserController::class);
Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');
Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.resetPassword');

/** Garages */
Route::resource('garages', GarageController::class);

/** Settings */
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::put('/', [SettingsController::class, 'update'])->name('update');

    // tests
    Route::post('/test/meta',          [SettingsController::class, 'testMeta'])->name('test.meta');
    Route::post('/test/twilio',        [SettingsController::class, 'testTwilio'])->name('test.twilio');
    Route::post('/test/meta-inline',   [SettingsController::class, 'testMetaInline'])->name('test.meta.inline');
    Route::post('/test/twilio-inline', [SettingsController::class, 'testTwilioInline'])->name('test.twilio.inline');
});

/** Meta Connect */
Route::prefix('meta')->name('meta.')->group(function () {
    Route::get('connect',       [\App\Http\Controllers\Admin\MetaConnectController::class, 'start'])->name('connect');
    Route::get('callback',      [\App\Http\Controllers\Admin\MetaConnectController::class, 'callback'])->name('callback');
    Route::post('select-page',  [\App\Http\Controllers\Admin\MetaConnectController::class, 'selectPage'])->name('select_page');
    Route::post('refresh',      [\App\Http\Controllers\Admin\MetaConnectController::class, 'refresh'])->name('refresh');
    Route::post('disconnect',   [\App\Http\Controllers\Admin\MetaConnectController::class, 'disconnect'])->name('disconnect');
});

/** Template preview */
Route::get('templates/{template}/preview', [TemplateController::class, 'preview'])->name('templates.preview');

/** Back-compat company settings URLs */
Route::get('settings/company', fn () => redirect()->route('admin.settings.index'))->name('settings.company.edit');
Route::put('settings/company', [SettingsController::class, 'update'])->name('settings.company.update');

/** Plans */
Route::resource('plans', PlanController::class)->except(['show']);

/** Vehicles + renewals */
Route::resource('vehicles', VehicleController::class);
Route::patch('vehicles/{vehicle}/renewals', [VehicleController::class, 'updateRenewals'])->name('vehicles.renewals.update');

/** Vehicle AJAX */
Route::get('ajax/models-by-make/{makeId}', [AjaxController::class, 'modelsByMake'])->name('ajax.models-by-make');
Route::post('ajax/find-or-create-vehicle', [VehicleController::class, 'findOrCreate'])->name('ajax.find-or-create-vehicle');

/** ★ AI (Control Center + Policy + Insights) */
Route::prefix('ai')->name('ai.')->group(function () {
    Route::get('/',        [AiSettingController::class, 'edit'])->name('edit');
    Route::post('/',       [AiSettingController::class, 'update'])->name('update');

    Route::get('/policy',  [AiPolicyController::class, 'edit'])->name('policy.edit');
    Route::post('/policy', [AiPolicyController::class, 'update'])->name('policy.update');

    Route::get('/insights', [AiInsightsController::class, 'index'])->name('insights');
});

/** ★ Business Profile & Escalation */
Route::get('business',  [BusinessProfileController::class, 'edit'])->name('business.edit');
Route::post('business', [BusinessProfileController::class, 'update'])->name('business.update');

/** ★ Unified Chat (Conversations + Thread) */
Route::prefix('chat')->name('chat.')->group(function () {
    Route::get('/',                [ChatController::class, 'index'])->name('index');
    Route::get('/{chat}',          [ChatController::class, 'show'])->name('show');
    Route::get('/{chat}/messages', [ChatController::class, 'messages'])->name('messages'); // polling
    Route::post('/{chat}/send',    [ChatController::class, 'send'])->name('send');        // human send
});

/** Health check */
Route::get('example', fn () => response()->json(['message' => 'Garage CRM API is working!']));
