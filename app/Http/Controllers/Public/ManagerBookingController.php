<?php

namespace App\Http\Controllers\Public;

use App\Events\BookingStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Booking\ManagerBookingToken;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManagerBookingController extends Controller
{
    public function show(string $token)
    {
        $tokenRow = ManagerBookingToken::where('token', $token)
            ->with([
                'opportunity.client',
                'opportunity.vehicle',
                'opportunity.vehicleMake',
                'opportunity.vehicleModel',
            ])
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
            ->with([
                'opportunity.client',
                'opportunity.vehicle',
                'opportunity.vehicleMake',
                'opportunity.vehicleModel',
            ])
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
            'slot' => ['required', 'in:morning,afternoon,evening,full_day'],
        ]);

        $companyId = (int) $tokenRow->company_id;
        $slot = strtolower(trim((string) $data['slot']));
        $bookingDate = Carbon::parse($data['booking_date'])->toDateString();

        $opportunity = $tokenRow->opportunity;

        /*
        |--------------------------------------------------------------------------
        | Find existing pending/scheduled booking for this opportunity
        |--------------------------------------------------------------------------
        | Important:
        | WhatsApp bot already creates a pending booking.
        | Manager confirmation should update that booking to scheduled,
        | not create another booking.
        |--------------------------------------------------------------------------
        */

        $existingBooking = Booking::query()
            ->where('company_id', $companyId)
            ->where('opportunity_id', $opportunity->id)
            ->where('is_archived', false)
            ->whereIn('status', [
                Booking::STATUS_PENDING,
                Booking::STATUS_SCHEDULED,
                Booking::STATUS_CONFIRMED,
            ])
            ->latest('id')
            ->first();

        $isAvailable = Booking::isSlotAvailable(
            bookingDate: $bookingDate,
            slot: $slot,
            companyId: $companyId,
            ignoreId: $existingBooking?->id
        );

        if (! $isAvailable) {
            return back()->withErrors([
                'slot' => 'Selected slot is already booked. Please choose another time.',
            ])->withInput();
        }

        $booking = DB::transaction(function () use (
            $tokenRow,
            $opportunity,
            $existingBooking,
            $companyId,
            $bookingDate,
            $slot
        ) {
            $booking = $existingBooking ?: new Booking();

            $booking->company_id = $companyId;
            $booking->client_id = $opportunity->client_id;
            $booking->opportunity_id = $opportunity->id;
            $booking->vehicle_id = $opportunity->vehicle_id;

            $booking->name = $opportunity->title ?: 'Manager confirmed booking';
            $booking->service_type = $opportunity->service_type ?: 'Service';
            $booking->priority = $opportunity->priority ?: 'medium';

            $booking->booking_date = $bookingDate;
            $booking->expected_close_date = $bookingDate;
            $booking->slot = $slot;

            /*
            |--------------------------------------------------------------------------
            | Manager confirmation = scheduled booking
            |--------------------------------------------------------------------------
            */

            $booking->status = Booking::STATUS_SCHEDULED;
            $booking->is_archived = false;

            $existingNotes = trim((string) $booking->notes);

            $booking->notes = trim($existingNotes . "\nConfirmed via manager link");

            $booking->confirmed_at = $booking->confirmed_at ?: now();
            $booking->state_changed_at = now();
            $booking->state_changed_by = null;

            $booking->save();

            $opportunity->update([
                'stage' => Opportunity::STAGE_APPOINTMENT,
                'expected_close_date' => $bookingDate,
                'next_follow_up' => $bookingDate,
            ]);

            $tokenRow->update([
                'used_at' => Carbon::now(),
            ]);

            DB::afterCommit(function () use ($booking) {
                event(new BookingStatusUpdated(
                    $booking->fresh(),
                    Booking::STATUS_SCHEDULED
                ));
            });

            Log::info('[ManagerBookingController] Booking confirmed by manager', [
                'booking_id' => $booking->id,
                'company_id' => $booking->company_id,
                'opportunity_id' => $booking->opportunity_id,
                'booking_date' => $booking->booking_date,
                'slot' => $booking->slot,
                'status' => $booking->status,
            ]);

            return $booking->fresh();
        });

        return view('public.booking-confirmed', compact('booking'));
    }
}