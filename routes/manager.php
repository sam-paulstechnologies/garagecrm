<?php

use App\Http\Controllers\Manager\BookingController as ManagerBookingController;
use App\Http\Controllers\Manager\ClientController as ManagerClientController;
use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Manager\GrowthController as ManagerGrowthController;
use App\Http\Controllers\Manager\InboxController as ManagerInboxController;
use App\Http\Controllers\Manager\InvoiceController as ManagerInvoiceController;
use App\Http\Controllers\Manager\JobController as ManagerJobController;
use App\Http\Controllers\Manager\LeadController as ManagerLeadController;
use App\Http\Controllers\Manager\OpportunityController as ManagerOpportunityController;
use App\Http\Controllers\Manager\SettingsController as ManagerSettingsController;
use App\Http\Controllers\Manager\TeamController as ManagerTeamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'active', 'force_password', 'role:manager'])
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
        | Inbox / Escalations / Conversations
        |--------------------------------------------------------------------------
        | IMPORTANT:
        | These JSON/API-style routes must stay above inbox/{lead}.
        | Otherwise Laravel may treat "list", "messages", "send" as {lead}.
        |--------------------------------------------------------------------------
        */
        Route::get('inbox', [ManagerInboxController::class, 'index'])
            ->name('inbox.index');

        Route::get('inbox/list', [ManagerInboxController::class, 'jsonList'])
            ->name('inbox.list');

        Route::get('inbox/messages/{conversation}', [ManagerInboxController::class, 'jsonMessages'])
            ->name('inbox.messages');

        Route::post('inbox/send', [ManagerInboxController::class, 'send'])
            ->name('inbox.send');

        Route::post('inbox/suggest-reply', [ManagerInboxController::class, 'suggestReply'])
            ->name('inbox.suggest-reply');

        Route::post('inbox/mark-read', [ManagerInboxController::class, 'markRead'])
            ->name('inbox.mark-read');

        /*
        |--------------------------------------------------------------------------
        | Old Lead-Based Inbox Routes
        |--------------------------------------------------------------------------
        */
        Route::get('inbox/{lead}', [ManagerInboxController::class, 'show'])
            ->name('inbox.show');

        Route::post('inbox/{lead}/reply', [ManagerInboxController::class, 'reply'])
            ->name('inbox.reply');

        Route::patch('inbox/{lead}/resume', [ManagerInboxController::class, 'resumeBot'])
            ->name('inbox.resume');

        /*
        |--------------------------------------------------------------------------
        | Backward Compatible Conversation Route Names
        |--------------------------------------------------------------------------
        */
        Route::get('escalations', [ManagerInboxController::class, 'index'])
            ->name('escalations');

        Route::get('conversation/{lead}', [ManagerInboxController::class, 'show'])
            ->name('conversation');

        Route::post('conversation/{lead}/reply', [ManagerInboxController::class, 'reply'])
            ->name('conversation.reply');

        Route::patch('conversation/{lead}/resume', [ManagerInboxController::class, 'resumeBot'])
            ->name('conversation.resume');

        /*
        |--------------------------------------------------------------------------
        | Leads
        |--------------------------------------------------------------------------
        */
        Route::get('leads', [ManagerLeadController::class, 'index'])
            ->name('leads.index');

        Route::patch('leads/{lead}/status', [ManagerLeadController::class, 'updateStatus'])
            ->name('leads.status');

        Route::patch('leads/{lead}/assign', [ManagerLeadController::class, 'assign'])
            ->name('leads.assign');

        Route::patch('leads/{lead}/follow-up', [ManagerLeadController::class, 'updateFollowUp'])
            ->name('leads.follow-up');

        /*
        |--------------------------------------------------------------------------
        | Opportunities
        |--------------------------------------------------------------------------
        */
        Route::get('opportunities', [ManagerOpportunityController::class, 'index'])
            ->name('opportunities.index');

        Route::get('opportunities/{opportunity}', [ManagerOpportunityController::class, 'show'])
            ->name('opportunities.show');

        Route::patch('opportunities/{opportunity}/stage', [ManagerOpportunityController::class, 'updateStage'])
            ->name('opportunities.stage');

        Route::patch('opportunities/{opportunity}/assign', [ManagerOpportunityController::class, 'assign'])
            ->name('opportunities.assign');

        Route::patch('opportunities/{opportunity}/follow-up', [ManagerOpportunityController::class, 'updateFollowUp'])
            ->name('opportunities.follow-up');

        Route::post('opportunities/{opportunity}/schedule-booking', [ManagerOpportunityController::class, 'scheduleBooking'])
            ->name('opportunities.schedule-booking');

        Route::patch('opportunities/{opportunity}/mark-won', [ManagerOpportunityController::class, 'markWon'])
            ->name('opportunities.mark-won');

        Route::patch('opportunities/{opportunity}/mark-lost', [ManagerOpportunityController::class, 'markLost'])
            ->name('opportunities.mark-lost');

        /*
        |--------------------------------------------------------------------------
        | Bookings
        |--------------------------------------------------------------------------
        */
        Route::get('bookings', [ManagerBookingController::class, 'index'])
            ->name('bookings.index');

        Route::get('bookings/{booking}', [ManagerBookingController::class, 'show'])
            ->name('bookings.show');

        Route::post('bookings/{booking}/confirm', [ManagerBookingController::class, 'confirm'])
            ->name('bookings.confirm');

        Route::patch('bookings/{booking}/reschedule', [ManagerBookingController::class, 'reschedule'])
            ->name('bookings.reschedule');

        Route::post('bookings/{booking}/reject', [ManagerBookingController::class, 'reject'])
            ->name('bookings.reject');

        Route::post('bookings/{booking}/convert-to-job', [ManagerBookingController::class, 'convertToJob'])
            ->name('bookings.convert-to-job');

        /*
        |--------------------------------------------------------------------------
        | Jobs
        |--------------------------------------------------------------------------
        */
        Route::get('jobs', [ManagerJobController::class, 'index'])
            ->name('jobs.index');

        Route::get('jobs/completed', [ManagerJobController::class, 'completed'])
            ->name('jobs.completed');

        Route::get('jobs/{job}', [ManagerJobController::class, 'show'])
            ->name('jobs.show');

        Route::patch('jobs/{job}/status', [ManagerJobController::class, 'updateStatus'])
            ->name('jobs.status');

        Route::patch('jobs/{job}/complete-with-invoice', [ManagerJobController::class, 'completeWithInvoice'])
            ->name('jobs.complete-with-invoice');

        Route::patch('jobs/{job}/assign', [ManagerJobController::class, 'assign'])
            ->name('jobs.assign');

        Route::patch('jobs/{job}/work-details', [ManagerJobController::class, 'updateWorkDetails'])
            ->name('jobs.work-details');

        /*
        |--------------------------------------------------------------------------
        | Invoices
        |--------------------------------------------------------------------------
        */
        Route::get('invoices', [ManagerInvoiceController::class, 'index'])
            ->name('invoices.index');

        Route::get('invoices/{invoice}', [ManagerInvoiceController::class, 'show'])
            ->name('invoices.show');

        Route::patch('invoices/{invoice}/mark-paid', [ManagerInvoiceController::class, 'markPaid'])
            ->name('invoices.mark-paid');

        Route::patch('invoices/{invoice}/mark-unpaid', [ManagerInvoiceController::class, 'markUnpaid'])
            ->name('invoices.mark-unpaid');

        /*
        |--------------------------------------------------------------------------
        | Clients
        |--------------------------------------------------------------------------
        */
        Route::get('clients', [ManagerClientController::class, 'index'])
            ->name('clients.index');

        /*
        |--------------------------------------------------------------------------
        | Team
        |--------------------------------------------------------------------------
        */
        Route::get('team', [ManagerTeamController::class, 'index'])
            ->name('team.index');

        /*
        |--------------------------------------------------------------------------
        | Growth
        |--------------------------------------------------------------------------
        | Manager-safe growth page.
        | View-only for now.
        |--------------------------------------------------------------------------
        */
        Route::get('growth', [ManagerGrowthController::class, 'index'])
            ->name('growth.index');

        /*
        |--------------------------------------------------------------------------
        | Settings
        |--------------------------------------------------------------------------
        | Manager-safe settings page.
        | Operational settings only.
        |--------------------------------------------------------------------------
        */
        Route::get('settings', [ManagerSettingsController::class, 'index'])
            ->name('settings.index');
    });