<?php

namespace App\Services\WhatsApp;

use App\Models\Client\Opportunity;
use App\Services\Booking\BookingLinkGenerator;

class ManagerBookingNotifier
{
    public function notify(Opportunity $opportunity): void
    {
        $link = app(BookingLinkGenerator::class)->generate($opportunity);

        $message = 
            "🛠 *Booking Required*\n\n" .
            "Client: {$opportunity->client->name}\n" .
            "Opportunity #: {$opportunity->id}\n\n" .
            "Please book a slot:\n{$link}";

        // You already have WhatsApp infra — reuse it
        whatsapp_send(
            to: $opportunity->assignedManager?->phone
                ?? config('whatsapp.default_manager'),
            message: $message
        );
    }
}
