<?php

namespace App\Services\Booking;

use App\Models\Job\Booking;
use App\Events\BookingStatusUpdated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class BookingStateService
{
    /** Allowed transitions */
    private const MAP = [
        Booking::STATUS_PENDING          => [Booking::STATUS_CONFIRMED, Booking::STATUS_SCHEDULED, Booking::STATUS_VEHICLE_RECEIVED, Booking::STATUS_CANCELED],
        Booking::STATUS_SCHEDULED        => [Booking::STATUS_CONFIRMED, Booking::STATUS_VEHICLE_RECEIVED, Booking::STATUS_CANCELED],
        Booking::STATUS_CONFIRMED        => [Booking::STATUS_VEHICLE_RECEIVED, Booking::STATUS_COMPLETED, Booking::STATUS_CANCELED],
        Booking::STATUS_VEHICLE_RECEIVED => [Booking::STATUS_COMPLETED, Booking::STATUS_CANCELED],
        Booking::STATUS_COMPLETED        => [],
        Booking::STATUS_CANCELED         => [],
    ];

    public function transition(Booking $booking, string $to): Booking
    {
        $from = strtolower(trim((string) $booking->status));
        $to   = strtolower(trim($to));

        if (!array_key_exists($from, self::MAP) || !in_array($to, self::MAP[$from], true)) {
            throw ValidationException::withMessages([
                'to' => "Illegal transition: {$from} → {$to}",
            ]);
        }

        return DB::transaction(function () use ($booking, $to) {
            $now   = Carbon::now();
            $actor = Auth::id();

            $booking->status = $to;

            if (Schema::hasColumn('bookings', 'state_changed_at')) {
                $booking->state_changed_at = $now;
            }

            if (Schema::hasColumn('bookings', 'state_changed_by')) {
                $booking->state_changed_by = $actor;
            }

            if ($to === Booking::STATUS_CONFIRMED && Schema::hasColumn('bookings', 'confirmed_at')) {
                $booking->confirmed_at = $now;
            }

            if ($to === Booking::STATUS_COMPLETED && Schema::hasColumn('bookings', 'completed_at')) {
                $booking->completed_at = $now;
            }

            if ($to === Booking::STATUS_CANCELED && Schema::hasColumn('bookings', 'cancelled_at')) {
                $booking->cancelled_at = $now;
            }

            $booking->save();

            event(new BookingStatusUpdated($booking, $to));

            return $booking->fresh();
        });
    }
}