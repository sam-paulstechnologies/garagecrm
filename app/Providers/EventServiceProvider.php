<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // ✅ Unified notifications (4-in-1)
        \App\Events\LeadCreated::class               => [
            \App\Listeners\SendUnifiedNotification::class,
            \App\Listeners\StartJourneyForLead::class,   // ⬅️ add this line
        ],
        \App\Events\OpportunityStatusUpdated::class  => [\App\Listeners\SendUnifiedNotification::class],
        \App\Events\BookingStatusUpdated::class      => [\App\Listeners\SendUnifiedNotification::class],
        \App\Events\JobCompleted::class              => [\App\Listeners\SendUnifiedNotification::class],

        // ♻️ Backward compat (optional):
        \App\Events\OpportunityStageChanged::class   => [\App\Listeners\SendUnifiedNotification::class],
    ];

    public function boot(): void {}
    public function shouldDiscoverEvents(): bool { return false; }
}
