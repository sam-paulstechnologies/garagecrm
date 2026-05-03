<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [

        /*
        |--------------------------------------------------------------------------
        | Lead lifecycle
        |--------------------------------------------------------------------------
        */

        \App\Events\LeadCreated::class => [

            // Core notification system
            \App\Listeners\SendUnifiedNotification::class,

            // Marketing journey automation
            \App\Listeners\StartJourneyForLead::class,

            // WhatsApp automation (single source of truth)
            \App\Listeners\HandleLeadCreatedOutbound::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Opportunity lifecycle
        |--------------------------------------------------------------------------
        */

        \App\Events\OpportunityStatusUpdated::class => [
            \App\Listeners\SendUnifiedNotification::class,
        ],

        \App\Events\OpportunityStageChanged::class => [
            \App\Listeners\SendUnifiedNotification::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Booking lifecycle
        |--------------------------------------------------------------------------
        */

        \App\Events\BookingStatusUpdated::class => [
            \App\Listeners\SendUnifiedNotification::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Job lifecycle
        |--------------------------------------------------------------------------
        */

        \App\Events\JobCompleted::class => [
            \App\Listeners\SendUnifiedNotification::class,
            \App\Listeners\Job\JobCompletedFeedback::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}