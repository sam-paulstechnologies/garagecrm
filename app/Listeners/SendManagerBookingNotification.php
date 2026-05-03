<?php

namespace App\Listeners;

use App\Events\OpportunityStatusUpdated;
use App\Services\WhatsApp\ManagerBookingNotifier;

class SendManagerBookingNotification
{
    public function handle(OpportunityStatusUpdated $event): void
    {
        if ($event->status !== 'ready for booking') {
            return;
        }

        app(ManagerBookingNotifier::class)
            ->notify($event->opportunity);
    }
}
