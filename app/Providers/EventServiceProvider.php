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
        |
        | SendUnifiedNotification:
        | - Email only now.
        | - WhatsApp has been removed from config/notify.php.
        |
        | StartJourneyForLead:
        | - Marketing / journey automation.
        |
        | HandleLeadCreatedOutbound:
        | - WhatsApp customer acknowledgement.
        | - Must use SendWhatsAppMessage::fireEvent() with DB mappings.
        |
        */

        \App\Events\LeadCreated::class => [
            \App\Listeners\SendUnifiedNotification::class,
            \App\Listeners\StartJourneyForLead::class,
            \App\Listeners\HandleLeadCreatedOutbound::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Opportunity lifecycle
        |--------------------------------------------------------------------------
        |
        | SendUnifiedNotification:
        | - Email only.
        |
        | SendManagerBookingNotification:
        | - Notifies manager when opportunity becomes ready for booking.
        |
        */

        \App\Events\OpportunityStatusUpdated::class => [
            \App\Listeners\SendUnifiedNotification::class,
            \App\Listeners\SendManagerBookingNotification::class,
        ],

        \App\Events\OpportunityStageChanged::class => [
            \App\Listeners\SendUnifiedNotification::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Booking lifecycle
        |--------------------------------------------------------------------------
        |
        | SendUnifiedNotification:
        | - Email only.
        |
        | SendManagerBookingNotification:
        | - Sends WhatsApp booking confirmation when booking becomes scheduled.
        |
        */

        \App\Events\BookingStatusUpdated::class => [
            \App\Listeners\SendUnifiedNotification::class,
            \App\Listeners\SendManagerBookingNotification::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | Job lifecycle
        |--------------------------------------------------------------------------
        |
        | SendUnifiedNotification:
        | - Email only.
        |
        | JobCompletedFeedback:
        | - Needs review later to ensure WhatsApp uses DB-mapped fireEvent().
        |
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