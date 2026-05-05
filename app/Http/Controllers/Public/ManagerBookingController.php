<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Booking\ManagerBookingToken;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagerBookingController extends Controller
{
    public function show(string $token)
    {
        $tokenRow = ManagerBookingToken::where('token', $token)
            ->with('opportunity.client')
            ->firstOrFail();

        abort_unless($tokenRow->isValid(), 403, 'Link expired');
        abort_unless($tokenRow->opportunity, 404, 'Opportunity not found.');
        abort_unless(
            (int) $tokenRow->opportunity->company_id === (int) $tokenRow->company_id,
            403,
            'Invalid booking link.'
        );

        $opportunity = $tokenRow->opportunity;

        return view('public.manager-booking', compact('tokenRow', 'opportunity'));
    }

    public function store(Request $request, string $token)
    {
        $tokenRow = ManagerBookingToken::where('token', $token)
            ->with('opportunity.client')
            ->firstOrFail();

        abort_unless($tokenRow->isValid(), 403, 'Link expired');
        abort_unless($tokenRow->opportunity, 404, 'Opportunity not found.');
        abort_unless(
            (int) $tokenRow->opportunity->company_id === (int) $tokenRow->company_id,
            403,
            'Invalid booking link.'
        );

        $data = $request->validate([
            'booking_date' => ['required', 'date'],
            'slot'         => ['required', 'in:morning,afternoon,evening,full_day'],
        ]);

        $slot = strtolower(trim($data['slot']));

        $isAvailable = Booking::isSlotAvailable(
            bookingDate: $data['booking_date'],
            slot: $slot,
            companyId: (int) $tokenRow->company_id
        );

        if (!$isAvailable) {
            return back()->withErrors([
                'slot' => 'Selected slot is already booked. Please choose another time.'
            ])->withInput();
        }

        $booking = DB::transaction(function () use ($tokenRow, $data, $slot) {
            $opportunity = $tokenRow->opportunity;

            $booking = Booking::create([
                'company_id'     => $tokenRow->company_id,
                'client_id'      => $opportunity->client_id,
                'opportunity_id' => $opportunity->id,
                'vehicle_id'     => $opportunity->vehicle_id,

                'name'           => $opportunity->title ?? 'Manager confirmed booking',
                'service_type'   => $opportunity->service_type,
                'priority'       => $opportunity->priority ?? 'medium',

                'booking_date'   => $data['booking_date'],
                'expected_close_date' => $data['booking_date'],
                'slot'           => $slot,

                'status'         => Booking::STATUS_CONFIRMED,
                'is_archived'    => false,

                'notes'          => 'Confirmed via manager link',
                'confirmed_at'   => now(),
                'state_changed_at' => now(),
                'state_changed_by' => null,
            ]);

            $opportunity->update([
                'stage' => Opportunity::STAGE_APPOINTMENT,
                'expected_close_date' => $data['booking_date'],
                'next_follow_up' => $data['booking_date'],
            ]);

            $tokenRow->update([
                'used_at' => Carbon::now(),
            ]);

            return $booking;
        });

        return view('public.booking-confirmed', compact('booking'));
    }
}