<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        // ✅ Unified notifications (4-in-1)
        \App\Events\LeadCreated::class               => [\App\Listeners\SendUnifiedNotification::class],
        \App\Events\OpportunityStatusUpdated::class  => [\App\Listeners\SendUnifiedNotification::class],
        \App\Events\BookingStatusUpdated::class      => [\App\Listeners\SendUnifiedNotification::class],
        \App\Events\JobCompleted::class              => [\App\Listeners\SendUnifiedNotification::class],

        // ♻️ Backward-compat with your existing event name (optional but safe):
        \App\Events\OpportunityStageChanged::class   => [\App\Listeners\SendUnifiedNotification::class],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Disable auto-discovery; we’re mapping explicitly above.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
