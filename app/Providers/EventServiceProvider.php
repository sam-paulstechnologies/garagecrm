<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Your unified notification + journey
        \App\Events\LeadCreated::class => [
            \App\Listeners\SendUnifiedNotification::class,
            \App\Listeners\StartJourneyForLead::class,
            // NEW: Welcome + 20-min follow-up
            \App\Listeners\Lead\LeadWelcomeAndFollowup::class,
        ],

        \App\Events\OpportunityStatusUpdated::class => [
            \App\Listeners\SendUnifiedNotification::class,
        ],

        \App\Events\BookingStatusUpdated::class => [
            \App\Listeners\SendUnifiedNotification::class,
        ],

        \App\Events\JobCompleted::class => [
            \App\Listeners\SendUnifiedNotification::class,
            // NEW: fire 'job.done.feedback'
            \App\Listeners\Job\JobCompletedFeedback::class,
        ],

        // Backward compat (optional)
        \App\Events\OpportunityStageChanged::class => [
            \App\Listeners\SendUnifiedNotification::class,
        ],
    ];

    public function boot(): void {}

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
