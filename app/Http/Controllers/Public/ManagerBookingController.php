<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking\ManagerBookingToken;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ManagerBookingController extends Controller
{
    public function show(string $token)
    {
        $tokenRow = ManagerBookingToken::where('token', $token)->firstOrFail();

        abort_unless($tokenRow->isValid(), 403, 'Link expired');

        $opportunity = $tokenRow->opportunity->load('client');

        return view('public.manager-booking', compact('tokenRow', 'opportunity'));
    }

    public function store(Request $request, string $token)
    {
        $tokenRow = ManagerBookingToken::where('token', $token)->firstOrFail();

        abort_unless($tokenRow->isValid(), 403, 'Link expired');

        $data = $request->validate([
            'booking_date' => 'required|date',
            'slot'         => 'required|string|max:50',
        ]);

        $slot = strtolower(trim($data['slot']));

        // =========================================================
        // 🔥 SLOT VALIDATION (CRITICAL FIX)
        // =========================================================
        $isAvailable = Booking::isSlotAvailable(
            bookingDate: $data['booking_date'],
            slot: $slot,
            companyId: $tokenRow->company_id
        );

        if (!$isAvailable) {
            return back()->withErrors([
                'slot' => 'Selected slot is already booked. Please choose another time.'
            ]);
        }

        // =========================================================
        // 🚀 CREATE BOOKING
        // =========================================================
        $booking = Booking::create([
            'company_id'     => $tokenRow->company_id,
            'client_id'      => $tokenRow->opportunity->client_id,
            'opportunity_id' => $tokenRow->opportunity_id,
            'vehicle_id'     => $tokenRow->opportunity?->vehicle_id,

            'booking_date'   => $data['booking_date'],
            'slot'           => $slot,

            'status'         => Booking::STATUS_CONFIRMED,
            'is_archived'    => false,

            'notes'          => 'Confirmed via manager link',
            'confirmed_at'   => now(),
        ]);

        // =========================================================
        // 🔥 UPDATE OPPORTUNITY (IMPORTANT)
        // =========================================================
        if ($tokenRow->opportunity) {
            $tokenRow->opportunity->update([
                'stage' => Opportunity::STAGE_APPOINTMENT,
                'expected_close_date' => $data['booking_date'],
                'next_follow_up' => $data['booking_date'],
            ]);
        }

        // =========================================================
        // 🔐 MARK TOKEN USED
        // =========================================================
        $tokenRow->update([
            'used_at' => Carbon::now()
        ]);

        return view('public.booking-confirmed', compact('booking'));
    }
}