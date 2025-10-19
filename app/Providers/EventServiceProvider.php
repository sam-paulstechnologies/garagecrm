<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * Keep this lean to avoid duplicate sends. Lead automation is already
     * handled by Lead::booted()->created -> TriggerEngine.
     */
    protected $listen = [
        // Generic notifications you already rely on
        \App\Events\OpportunityStatusUpdated::class => [
            \App\Listeners\SendUnifiedNotification::class,
        ],

        \App\Events\BookingStatusUpdated::class => [
            \App\Listeners\SendUnifiedNotification::class,
        ],

        \App\Events\JobCompleted::class => [
            \App\Listeners\SendUnifiedNotification::class,
            \App\Listeners\Job\JobCompletedFeedback::class,
        ],

        // Backward compatibility (optional)
        \App\Events\OpportunityStageChanged::class => [
            \App\Listeners\SendUnifiedNotification::class,
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
