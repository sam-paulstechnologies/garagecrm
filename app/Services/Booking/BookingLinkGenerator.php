<?php

namespace App\Services\Booking;

use App\Models\Booking\ManagerBookingToken;
use App\Models\Client\Opportunity;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingLinkGenerator
{
    public function generate(Opportunity $opportunity): string
    {
        $token = ManagerBookingToken::create([
            'company_id'     => $opportunity->company_id,
            'opportunity_id' => $opportunity->id,
            'token'          => Str::random(48),
            'expires_at'     => Carbon::now()->addHours(24),
        ]);

        return route('manager.booking.show', $token->token);
    }
}
