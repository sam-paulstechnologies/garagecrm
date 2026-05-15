<?php

use App\Http\Controllers\Manager\BookingController as ManagerBookingController;
use App\Http\Controllers\Manager\ClientController as ManagerClientController;
use App\Http\Controllers\Manager\DashboardController;
use App\Http\Controllers\Manager\InboxController as ManagerInboxController;
use App\Http\Controllers\Manager\JobController as ManagerJobController;
use App\Http\Controllers\Manager\LeadController as ManagerLeadController;
use App\Http\Controllers\Manager\OpportunityController as ManagerOpportunityController;
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
        */
        Route::get('inbox', [ManagerInboxController::class, 'index'])
            ->name('inbox.index');

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
        | These keep older manager dashboard/sidebar links working.
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

        Route::patch('opportunities/{opportunity}/stage', [ManagerOpportunityController::class, 'updateStage'])
            ->name('opportunities.stage');

        Route::patch('opportunities/{opportunity}/assign', [ManagerOpportunityController::class, 'assign'])
            ->name('opportunities.assign');

        Route::patch('opportunities/{opportunity}/follow-up', [ManagerOpportunityController::class, 'updateFollowUp'])
            ->name('opportunities.follow-up');

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

        Route::post('bookings/{booking}/reject', [ManagerBookingController::class, 'reject'])
            ->name('bookings.reject');

        Route::post('bookings/{booking}/convert-to-job', [ManagerBookingController::class, 'convertToJob'])
            ->name('bookings.convert-to-job');

        /*
        |--------------------------------------------------------------------------
        | Jobs
        |--------------------------------------------------------------------------
        | IMPORTANT:
        | jobs/completed must stay above jobs/{job}, otherwise Laravel may treat
        | "completed" as the {job} route parameter.
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

        Route::patch('jobs/{job}/assign', [ManagerJobController::class, 'assign'])
            ->name('jobs.assign');

        Route::patch('jobs/{job}/work-details', [ManagerJobController::class, 'updateWorkDetails'])
            ->name('jobs.work-details');

        /*
        |--------------------------------------------------------------------------
        | Clients
        |--------------------------------------------------------------------------
        | Manager access is read-only for now.
        |--------------------------------------------------------------------------
        */
        Route::get('clients', [ManagerClientController::class, 'index'])
            ->name('clients.index');

        /*
        |--------------------------------------------------------------------------
        | Team
        |--------------------------------------------------------------------------
        | Basic team listing for manager view.
        |--------------------------------------------------------------------------
        */
        Route::get('team', [ManagerTeamController::class, 'index'])
            ->name('team.index');

        /*
        |--------------------------------------------------------------------------
        | Invoices - Temporary placeholder
        |--------------------------------------------------------------------------
        | Invoice manager view can be added later.
        |--------------------------------------------------------------------------
        */
        Route::view('invoices', 'manager.placeholder')
            ->name('invoices.index');
    });