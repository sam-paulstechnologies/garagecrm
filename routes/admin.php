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
    AiSettingController,
    BusinessProfileController,
    AiPolicyController,
    AiInsightsController,
    SlaDashboardController,
    WhatsAppPerformanceController,
    WhatsAppSettingController
};

use App\Http\Controllers\Admin\InboxController;
use App\Http\Controllers\Admin\ConversationController;
use App\Http\Controllers\Admin\SmartReplyController;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tenant\ClientBookingController;

/*
|--------------------------------------------------------------------------
| Admin Routes
| Wrapped by RouteServiceProvider with:
| middleware(['web','auth','active','force_password'])
| prefix('admin')->as('admin.')
|--------------------------------------------------------------------------
*/

/** Global route parameter patterns */
Route::pattern('booking', '[0-9]+');
Route::pattern('client', '[0-9]+');
Route::pattern('job', '[0-9]+');
Route::pattern('opportunity', '[0-9]+');
Route::pattern('invoice', '[0-9]+');
Route::pattern('user', '[0-9]+');
Route::pattern('garage', '[0-9]+');
Route::pattern('vehicle', '[0-9]+');
Route::pattern('communication', '[0-9]+');
Route::pattern('lead', '[0-9]+');
Route::pattern('conversation', '[0-9]+');

/** Dashboard */
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

/** SLA Dashboard */
Route::get('sla-dashboard', [SlaDashboardController::class, 'index'])->name('sla_dashboard');

/** Calendar */
Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
Route::get('calendar/events', [CalendarController::class, 'events'])->name('calendar.events');

/** Profile */
Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

/** Clients */
Route::get('clients/archived', [ClientController::class, 'archived'])->name('clients.archived');
Route::post('clients/{client}/archive', [ClientController::class, 'archive'])->name('clients.archive');
Route::post('clients/{client}/restore', [ClientController::class, 'restore'])->name('clients.restore');

Route::get('clients/{client}/bookings', [ClientBookingController::class, 'index'])->name('clients.bookings');

Route::post('clients/{client}/notes', [ClientController::class, 'storeNote'])->name('clients.notes.store');
Route::get('clients/{client}/notes', [ClientController::class, 'notesIndex'])->name('clients.notes.index');

Route::get('clients/import', [ClientController::class, 'importForm'])->name('clients.import.form');
Route::post('clients/import', [ClientController::class, 'import'])->name('clients.import');

Route::resource('clients', ClientController::class);

/** Client Files */
Route::prefix('clients/{client}/files')->name('clients.files.')->group(function () {
    Route::get('/', [FileController::class, 'index'])->name('index');
    Route::get('/create', [FileController::class, 'create'])->name('create');
    Route::post('/', [FileController::class, 'store'])->name('store');
    Route::delete('/{file}', [FileController::class, 'destroy'])->name('destroy');
});

/** Leads */
Route::patch('leads/{lead}/toggle-hot', [LeadController::class, 'toggleHot'])->name('leads.toggleHot');
Route::patch('leads/{lead}/assign', [LeadController::class, 'assign'])->name('leads.assign');
Route::post('leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
Route::patch('leads/{lead}/touch', [LeadController::class, 'touchContacted'])->name('leads.touch');

Route::get('leads/{lead}/copilot/meta', [LeadController::class, 'copilotMeta'])->name('leads.copilot.meta');
Route::post('leads/{lead}/copilot/suggest-reply', [LeadController::class, 'copilotSuggestReply'])->name('leads.copilot.suggest');
Route::post('leads/{lead}/copilot/quick-booking', [LeadController::class, 'copilotQuickBooking'])->name('leads.copilot.quick-booking');
Route::post('leads/{lead}/copilot/followup', [LeadController::class, 'copilotScheduleFollowup'])->name('leads.copilot.followup');
Route::post('leads/{lead}/copilot/send-template', [LeadController::class, 'copilotSendTemplate'])->name('leads.copilot.send-template');

/** Lead Import + Duplicates */
Route::get('leads/import/meta', [LeadImportController::class, 'showMetaForm'])->name('leads.import.meta');
Route::post('leads/import/meta', [LeadImportController::class, 'importFromMeta'])->name('leads.import.meta.run');

Route::get('leads/duplicates', [LeadDuplicateController::class, 'index'])->name('leads.duplicates.index');
Route::post('leads/duplicates/window', [LeadDuplicateController::class, 'updateWindow'])->name('leads.duplicates.update-window');

Route::resource('leads', LeadController::class);

/** Opportunities */
Route::get('opportunities/archived', [OpportunityController::class, 'archived'])->name('opportunities.archived');
Route::put('opportunities/{opportunity}/restore', [OpportunityController::class, 'restore'])->name('opportunities.restore');
Route::resource('opportunities', OpportunityController::class);

/** Bookings */
Route::get('bookings/archived', [BookingController::class, 'archived'])->name('bookings.archived');
Route::put('bookings/{booking}/archive', [BookingController::class, 'archive'])->name('bookings.archive');
Route::put('bookings/{booking}/restore', [BookingController::class, 'restore'])->name('bookings.restore');
Route::resource('bookings', BookingController::class);

/** Jobs */
Route::get('jobs/archived', [JobController::class, 'archived'])->name('jobs.archived');
Route::post('jobs/{job}/archive', [JobController::class, 'archive'])->name('jobs.archive');
Route::post('jobs/{job}/restore', [JobController::class, 'restore'])->name('jobs.restore');
Route::post('jobs/{job}/card/upload', [JobController::class, 'uploadCard'])->name('jobs.card.upload');
Route::resource('jobs', JobController::class);

/** Invoices */
Route::get('ajax/jobs-by-client/{client}', [InvoiceController::class, 'jobsByClient'])->name('ajax.jobs-by-client');
Route::resource('invoices', InvoiceController::class);
Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
Route::get('invoices/{invoice}/view', [InvoiceController::class, 'view'])->name('invoices.view');
Route::post('jobs/{job}/invoices/upload', [InvoiceController::class, 'uploadForJob'])->name('jobs.invoices.upload');
Route::post('clients/{client}/invoices/upload', [InvoiceController::class, 'uploadForClient'])->name('clients.invoices.upload');
Route::post('invoices/{invoice}/primary', [InvoiceController::class, 'makePrimary'])->name('invoices.primary');

/** Communications */
Route::resource('communications', CommunicationController::class);
Route::get('communications/followups', [CommunicationController::class, 'followups'])->name('communications.followups');
Route::patch('communications/{communication}/complete', [CommunicationController::class, 'complete'])->name('communications.complete');
Route::get('communications/export/csv', [CommunicationController::class, 'exportCsv'])->name('communications.export.csv');

/** ✅ FIX: communication logs (alias-safe) */
Route::get('communication/logs', fn () => redirect()->route('admin.communications.index'))
    ->name('communication.logs');

/** Clients → Communications */
Route::get('clients/{client}/communications', [CommunicationController::class, 'indexForClient'])
    ->name('clients.communications.index');

Route::post('clients/{client}/communications', [CommunicationController::class, 'storeForClient'])
    ->name('clients.communications.store');

Route::get('ajax/communications/due-count', [CommunicationController::class, 'dueCount'])
    ->name('ajax.communications.due-count');

/** Marketing */
Route::prefix('marketing')->name('marketing.')->group(function () {
    Route::resource('campaigns', \App\Http\Controllers\Admin\Marketing\CampaignController::class);
    Route::resource('triggers', \App\Http\Controllers\Admin\Marketing\TriggerController::class)->except(['show']);
    Route::post('campaigns/{campaign}/activate', [\App\Http\Controllers\Admin\Marketing\CampaignController::class, 'activate'])->name('campaigns.activate');
    Route::post('campaigns/{campaign}/pause', [\App\Http\Controllers\Admin\Marketing\CampaignController::class, 'pause'])->name('campaigns.pause');
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
});

/** AI */
Route::prefix('ai')->name('ai.')->group(function () {
    Route::get('/', [AiSettingController::class, 'edit'])->name('edit');
    Route::put('/', [AiSettingController::class, 'update'])->name('update');
    Route::get('policies', [AiPolicyController::class, 'index'])->name('policies.index');
    Route::post('policies', [AiPolicyController::class, 'store'])->name('policies.store');
    Route::put('policies/{policy}', [AiPolicyController::class, 'update'])->name('policies.update');
    Route::get('insights', [AiInsightsController::class, 'index'])->name('insights.index');
});

/** Business Profile */
Route::prefix('business')->name('business.')->group(function () {
    Route::get('/', [BusinessProfileController::class, 'edit'])->name('edit');
    Route::put('/', [BusinessProfileController::class, 'update'])->name('update');
});

/** Plans */
Route::resource('plans', PlanController::class)->except(['show']);
Route::post('plans/{plan}/subscribe', [PlanController::class, 'subscribe'])->name('plans.subscribe');

/** WhatsApp */
Route::get('whatsapp/performance', [WhatsAppPerformanceController::class, 'index'])
    ->name('whatsapp.performance.index');

Route::get('whatsapp/settings', [WhatsAppSettingController::class, 'edit'])
    ->name('whatsapp.settings.edit');

Route::post('whatsapp/settings', [WhatsAppSettingController::class, 'save'])
    ->name('whatsapp.settings.save');

/** Unified Chat */
Route::prefix('chat')->name('chat.')->group(function () {
    Route::get('/', [InboxController::class, 'index'])->name('index');
    Route::get('/json/list', [InboxController::class, 'jsonList'])->name('json.list');
    Route::get('/{conversation}', [InboxController::class, 'show'])->name('show');
    Route::get('/{conversation}/messages', [ConversationController::class, 'messages'])->name('messages');
    Route::post('/{conversation}/send', [ConversationController::class, 'send'])->name('send');
    Route::post('/{conversation}/mark-read', [ConversationController::class, 'markRead'])->name('mark-read');
    Route::post('/{conversation}/smart-replies', [SmartReplyController::class, 'suggest'])->name('smart-replies');
});

/** Health */
Route::get('example', fn () => response()->json(['message' => 'Garage CRM API is working!']));
