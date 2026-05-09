<?php

namespace App\Observers;

use App\Events\BookingStatusUpdated;
use App\Models\Job\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    /*
    |--------------------------------------------------------------------------
    | Booking Observer
    |--------------------------------------------------------------------------
    |
    | IMPORTANT:
    |
    | Booking status transitions are handled by:
    |
    |   App\Services\Booking\BookingStateService
    |
    | That service dispatches:
    |
    |   BookingStatusUpdated
    |
    | after the DB transaction commits.
    |
    | Therefore, this observer must NOT dispatch status-change events,
    | otherwise booking WhatsApp/email notifications can be duplicated.
    |
    */

    protected const DATE_FIELDS = [
        'booking_date',
        'expected_close_date',
        'slot',
    ];

    public function updated(Booking $booking): void
    {
        /*
        |--------------------------------------------------------------------------
        | Status changes
        |--------------------------------------------------------------------------
        |
        | Do not dispatch BookingStatusUpdated here.
        | BookingStateService is the single source of truth for status changes.
        |
        */

        if ($booking->wasChanged('status')) {
            Log::info('[BookingObserver] Status change observed but event dispatch skipped', [
                'booking_id' => $booking->id,
                'company_id' => $booking->company_id,
                'old_status' => $booking->getOriginal('status'),
                'new_status' => $booking->status,
                'reason'     => 'BookingStateService owns BookingStatusUpdated dispatch',
            ]);

            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Date / slot changes
        |--------------------------------------------------------------------------
        |
        | Reschedule is not a status transition.
        | We keep this observer responsible for detecting schedule changes.
        |
        | This fires after commit to avoid WhatsApp/email firing on rollback.
        |
        */

        $dateChanged = collect(self::DATE_FIELDS)
            ->first(fn ($field) => $booking->wasChanged($field));

        if (! $dateChanged) {
            return;
        }

        DB::afterCommit(function () use ($booking, $dateChanged) {
            $freshBooking = $booking->fresh();

            if (! $freshBooking) {
                return;
            }

            Log::info('[BookingObserver] Booking reschedule event dispatched', [
                'booking_id'    => $freshBooking->id,
                'company_id'    => $freshBooking->company_id,
                'changed_field' => $dateChanged,
            ]);

            event(new BookingStatusUpdated($freshBooking, 'rescheduled'));
        });
    }
}