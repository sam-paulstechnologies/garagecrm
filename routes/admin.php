<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\{
    AjaxController,
    BookingController,
    ClientController,
    ClientDocumentController,
    CommunicationController,
    DashboardController,
    DemoController,
    FileController,
    GarageController,
    InvoiceController,
    JobController,
    LeadController,
    LeadImportController,
    LeadDuplicateController,
    LeadSourceController,
    OpportunityController,
    PlanController,
    SettingsController,
    LaunchSetupController,
    UserController,
    VehicleController,
    CalendarController,
    TemplateController,
    AiSettingController,
    BusinessProfileController,
    AiPolicyController,
    AiInsightsController,
    AiSuggestionsController,
    SlaDashboardController,
    WhatsAppPerformanceController,
    WhatsAppSettingController,
    InboxController,
    ConversationController,
    SmartReplyController,
    JourneyTimelineController,
    AudienceController,
    AudienceSegmentationController,
    DuplicateClientsController,
    MetaConnectController,
    DocumentInboxController,
    CommunicationLogController
};

use App\Http\Controllers\Admin\Marketing\CampaignController as MarketingCampaignController;
use App\Http\Controllers\Admin\Marketing\TriggerController as MarketingTriggerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tenant\ClientBookingController;
use App\Http\Controllers\Public\ManagerBookingController;

/*
|--------------------------------------------------------------------------
| PUBLIC MANAGER BOOKING — NO LOGIN
|--------------------------------------------------------------------------
*/
Route::get('/manager/booking/{token}', [ManagerBookingController::class, 'show'])
    ->name('manager.booking.show');

Route::post('/manager/booking/{token}', [ManagerBookingController::class, 'store'])
    ->name('manager.booking.store');

/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['web', 'auth', 'active', 'force_password', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Lead Sources
        |--------------------------------------------------------------------------
        */
        Route::prefix('lead-sources')->name('lead-sources.')->group(function () {

            Route::get('/', [LeadSourceController::class, 'index'])
                ->name('index');

            Route::get('/whatsapp', [LeadSourceController::class, 'whatsapp'])
                ->name('whatsapp');

            Route::prefix('website')->name('website.')->group(function () {
                Route::get('/', [LeadSourceController::class, 'websiteIndex'])
                    ->name('index');

                Route::post('/', [LeadSourceController::class, 'storeWebsite'])
                    ->name('store');

                Route::get('{leadSource}', [LeadSourceController::class, 'websiteShow'])
                    ->name('show');
            });

            Route::get('/meta', [LeadSourceController::class, 'meta'])
                ->name('meta');

            Route::get('/meta/connect', [MetaConnectController::class, 'start'])
                ->name('meta.connect');

            Route::get('/meta/callback', [MetaConnectController::class, 'callback'])
                ->name('meta.callback');

            Route::post('/meta/select-page', [MetaConnectController::class, 'selectPage'])
                ->name('meta.select-page');

            Route::post('/meta/refresh', [MetaConnectController::class, 'refresh'])
                ->name('meta.refresh');

            Route::post('/meta/disconnect', [MetaConnectController::class, 'disconnect'])
                ->name('meta.disconnect');
        });

        /*
        |--------------------------------------------------------------------------
        | Route Parameter Patterns
        |--------------------------------------------------------------------------
        */
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
        Route::pattern('enrollment', '[0-9]+');
        Route::pattern('audience', '[0-9]+');
        Route::pattern('audienceSegmentation', '[0-9]+');
        Route::pattern('candidate', '[0-9]+');
        Route::pattern('doc', '[0-9]+');
        Route::pattern('campaign', '[0-9]+');
        Route::pattern('trigger', '[0-9]+');

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('sla-dashboard', [SlaDashboardController::class, 'index'])
            ->name('sla_dashboard');

        /*
        |--------------------------------------------------------------------------
        | Calendar
        |--------------------------------------------------------------------------
        */
        Route::get('calendar', [CalendarController::class, 'index'])
            ->name('calendar.index');

        Route::get('calendar/events', [CalendarController::class, 'events'])
            ->name('calendar.events');

        /*
        |--------------------------------------------------------------------------
        | Profile
        |--------------------------------------------------------------------------
        */
        Route::get('profile', [ProfileController::class, 'edit'])
            ->name('profile.edit');

        Route::patch('profile', [ProfileController::class, 'update'])
            ->name('profile.update');

        Route::delete('profile', [ProfileController::class, 'destroy'])
            ->name('profile.destroy');

        /*
        |--------------------------------------------------------------------------
        | Settings
        |--------------------------------------------------------------------------
        */
        Route::get('settings', [SettingsController::class, 'index'])
            ->name('settings.index');

        Route::post('settings', [SettingsController::class, 'update'])
            ->name('settings.update');

        Route::post('settings/test-meta', [SettingsController::class, 'testMetaInline'])
            ->name('settings.test-meta');

        Route::post('settings/test-twilio', [SettingsController::class, 'testTwilioInline'])
            ->name('settings.test-twilio');

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Settings
        |--------------------------------------------------------------------------
        | Primary current URL:
        | /admin/whatsapp/settings
        |
        | Backward-compatible alternate URL:
        | /admin/settings/whatsapp
        |--------------------------------------------------------------------------
        */
        Route::get('whatsapp/settings', [WhatsAppSettingController::class, 'edit'])
            ->name('whatsapp.settings.edit');

        Route::put('whatsapp/settings', [WhatsAppSettingController::class, 'update'])
            ->name('whatsapp.settings.update');

        Route::post('whatsapp/settings/uat-reset', [WhatsAppSettingController::class, 'resetUatByPhone'])
            ->name('whatsapp.settings.uat-reset');

        Route::get('settings/whatsapp', [WhatsAppSettingController::class, 'edit'])
            ->name('whatsapp.settings.edit.alt');

        Route::put('settings/whatsapp', [WhatsAppSettingController::class, 'update'])
            ->name('whatsapp.settings.update.alt');

        Route::post('settings/whatsapp/uat-reset', [WhatsAppSettingController::class, 'resetUatByPhone'])
            ->name('whatsapp.settings.uat-reset.alt');

        /*
        |--------------------------------------------------------------------------
        | Audience Segmentation
        |--------------------------------------------------------------------------
        */
        Route::get('settings/audience-segmentation', [AudienceSegmentationController::class, 'index'])
            ->name('audience-segmentations.index');

        Route::patch('settings/audience-segmentation/{audienceSegmentation}/toggle', [AudienceSegmentationController::class, 'toggle'])
            ->name('audience-segmentations.toggle');

        /*
        |--------------------------------------------------------------------------
        | Launch Setup
        |--------------------------------------------------------------------------
        */
        Route::get('settings/launch-setup', [LaunchSetupController::class, 'edit'])
            ->name('settings.launch-setup.edit');

        Route::put('settings/launch-setup', [LaunchSetupController::class, 'update'])
            ->name('settings.launch-setup.update');

        /*
        |--------------------------------------------------------------------------
        | Documents Inbox
        |--------------------------------------------------------------------------
        */
        Route::get('documents', [DocumentInboxController::class, 'index'])
            ->name('documents.index');

        Route::get('documents/{doc}', [DocumentInboxController::class, 'show'])
            ->name('documents.show');

        Route::post('documents/{doc}/assign', [DocumentInboxController::class, 'assign'])
            ->name('documents.assign');

        /*
        |--------------------------------------------------------------------------
        | Clients & Vehicles
        |--------------------------------------------------------------------------
        */
        Route::get('clients/archived', [ClientController::class, 'archived'])
            ->name('clients.archived');

        Route::post('clients/{client}/archive', [ClientController::class, 'archive'])
            ->name('clients.archive');

        Route::post('clients/{client}/restore', [ClientController::class, 'restore'])
            ->name('clients.restore');

        Route::get('clients/{client}/bookings', [ClientBookingController::class, 'index'])
            ->name('clients.bookings');

        Route::post('clients/{client}/notes', [ClientController::class, 'storeNote'])
            ->name('clients.notes.store');

        Route::get('clients/{client}/notes', [ClientController::class, 'notesIndex'])
            ->name('clients.notes.index');

        Route::post('clients/{client}/documents', [ClientDocumentController::class, 'store'])
            ->name('clients.documents.store');

        Route::post('clients/{client}/documents/inbox-upload', [DocumentInboxController::class, 'uploadForClient'])
            ->name('documents.upload-for-client');

        Route::get('clients/import', [ClientController::class, 'importForm'])
            ->name('clients.import.form');

        Route::post('clients/import', [ClientController::class, 'import'])
            ->name('clients.import');

        Route::resource('clients', ClientController::class);
        Route::resource('vehicles', VehicleController::class);

        /*
        |--------------------------------------------------------------------------
        | Leads
        |--------------------------------------------------------------------------
        */
        Route::get('leads/qualified', [LeadController::class, 'qualified'])
            ->name('leads.qualified');

        Route::get('leads/disqualified', [LeadController::class, 'disqualified'])
            ->name('leads.disqualified');

        /*
        |--------------------------------------------------------------------------
        | Lead Import
        |--------------------------------------------------------------------------
        */
        Route::get('leads/import', [LeadController::class, 'importOptions'])
            ->name('leads.import.options');

        Route::get('leads/import/excel', [LeadImportController::class, 'showCsvForm'])
            ->name('leads.import.upload');

        Route::post('leads/import/excel', [LeadImportController::class, 'importFromCsv'])
            ->name('leads.import.process');

        Route::get('leads/import/sample', function () {
            return response()->download(public_path('samples/sample_lead_import.csv'));
        })->name('leads.import.sample');

        Route::get('leads/import/custom-form', [LeadController::class, 'customForm'])
            ->name('leads.custom-form');

        /*
        |--------------------------------------------------------------------------
        | Lead Actions
        |--------------------------------------------------------------------------
        */
        Route::patch('leads/{lead}/toggle-hot', [LeadController::class, 'toggleHot'])
            ->name('leads.toggleHot');

        /*
        |--------------------------------------------------------------------------
        | Lead Duplicates
        |--------------------------------------------------------------------------
        */
        Route::get('leads/duplicates', [LeadDuplicateController::class, 'index'])
            ->name('leads.duplicates.index');

        Route::post('leads/duplicates/update-window', [LeadDuplicateController::class, 'updateWindow'])
            ->name('leads.duplicates.update-window');

        Route::resource('leads', LeadController::class);

        /*
        |--------------------------------------------------------------------------
        | Opportunities
        |--------------------------------------------------------------------------
        */
        Route::get('opportunities/archived', [OpportunityController::class, 'archived'])
            ->name('opportunities.archived');

        Route::put('opportunities/{opportunity}/restore', [OpportunityController::class, 'restore'])
            ->name('opportunities.restore');

        Route::resource('opportunities', OpportunityController::class);

        /*
        |--------------------------------------------------------------------------
        | Bookings
        |--------------------------------------------------------------------------
        */
        Route::get('bookings/archived', [BookingController::class, 'archived'])
            ->name('bookings.archived');

        Route::put('bookings/{booking}/archive', [BookingController::class, 'archive'])
            ->name('bookings.archive');

        Route::put('bookings/{booking}/restore', [BookingController::class, 'restore'])
            ->name('bookings.restore');

        Route::resource('bookings', BookingController::class);

        /*
        |--------------------------------------------------------------------------
        | Jobs
        |--------------------------------------------------------------------------
        */
        Route::get('jobs/completed', [JobController::class, 'completed'])
            ->name('jobs.completed');

        Route::get('jobs/archived', [JobController::class, 'archived'])
            ->name('jobs.archived');

        Route::post('jobs/{job}/archive', [JobController::class, 'archive'])
            ->name('jobs.archive');

        Route::post('jobs/{job}/restore', [JobController::class, 'restore'])
            ->name('jobs.restore');

        Route::post('jobs/{job}/card/upload', [JobController::class, 'uploadCard'])
            ->name('jobs.card.upload');

        Route::resource('jobs', JobController::class)->except(['destroy']);

        /*
        |--------------------------------------------------------------------------
        | Invoices
        |--------------------------------------------------------------------------
        */
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])
            ->name('invoices.download');

        Route::resource('invoices', InvoiceController::class);

        /*
        |--------------------------------------------------------------------------
        | Communications
        |--------------------------------------------------------------------------
        */
        Route::get('communications/followups', [CommunicationController::class, 'followUps'])
            ->name('communications.followups');

        Route::patch('communications/{communication}/complete', [CommunicationController::class, 'complete'])
            ->name('communications.complete');

        Route::resource('communications', CommunicationController::class);

        Route::get('communication-logs', [CommunicationLogController::class, 'index'])
            ->name('communication-logs.index');

        /*
        |--------------------------------------------------------------------------
        | Marketing
        |--------------------------------------------------------------------------
        */
        Route::prefix('marketing')->name('marketing.')->group(function () {
            Route::get('campaigns', [MarketingCampaignController::class, 'index'])
                ->name('campaigns.index');

            Route::get('campaigns/create', [MarketingCampaignController::class, 'create'])
                ->name('campaigns.create');

            Route::post('campaigns', [MarketingCampaignController::class, 'store'])
                ->name('campaigns.store');

            Route::get('campaigns/{campaign}/edit', [MarketingCampaignController::class, 'edit'])
                ->name('campaigns.edit');

            Route::put('campaigns/{campaign}', [MarketingCampaignController::class, 'update'])
                ->name('campaigns.update');

            Route::post('campaigns/{campaign}/activate', [MarketingCampaignController::class, 'activate'])
                ->name('campaigns.activate');

            Route::post('campaigns/{campaign}/pause', [MarketingCampaignController::class, 'pause'])
                ->name('campaigns.pause');

            Route::get('triggers', [MarketingTriggerController::class, 'index'])
                ->name('triggers.index');

            Route::get('triggers/create', [MarketingTriggerController::class, 'create'])
                ->name('triggers.create');

            Route::post('triggers', [MarketingTriggerController::class, 'store'])
                ->name('triggers.store');

            Route::get('triggers/{trigger}/edit', [MarketingTriggerController::class, 'edit'])
                ->name('triggers.edit');

            Route::put('triggers/{trigger}', [MarketingTriggerController::class, 'update'])
                ->name('triggers.update');

            Route::delete('triggers/{trigger}', [MarketingTriggerController::class, 'destroy'])
                ->name('triggers.destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | AI
        |--------------------------------------------------------------------------
        */
        Route::prefix('ai')->name('ai.')->group(function () {
            Route::get('/', [AiSettingController::class, 'edit'])
                ->name('edit');

            Route::put('/', [AiSettingController::class, 'update'])
                ->name('update');

            Route::get('policy', [AiPolicyController::class, 'edit'])
                ->name('policy.edit');

            Route::put('policy', [AiPolicyController::class, 'update'])
                ->name('policy.update');

            Route::get('insights', [AiInsightsController::class, 'index'])
                ->name('insights.index');

            Route::get('suggestions', [AiSuggestionsController::class, 'index'])
                ->name('suggestions.index');
        });

        /*
        |--------------------------------------------------------------------------
        | Health
        |--------------------------------------------------------------------------
        */
        Route::get('example', fn () =>
            response()->json(['message' => 'Garage CRM Admin routes working'])
        );

        Route::get('inbox', function () {
            return inertia('Admin/Inbox/Index');
        })->name('inbox.index');

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Inbox Popup
        |--------------------------------------------------------------------------
        */
        Route::prefix('inbox')->name('inbox.')->group(function () {
            Route::get('/list', [InboxController::class, 'jsonList'])
                ->name('list');

            Route::get('/messages/{conversation}', [InboxController::class, 'jsonMessages'])
                ->name('messages');

            Route::post('/send', [InboxController::class, 'send'])
                ->name('send');
        });
    });