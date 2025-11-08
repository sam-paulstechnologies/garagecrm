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
        'pending'          => ['confirmed','scheduled','vehicle_received','canceled'],
        'scheduled'        => ['confirmed','vehicle_received','canceled'],
        'confirmed'        => ['vehicle_received','completed','canceled'],
        'vehicle_received' => ['completed','canceled'],
        'completed'        => [],
        'canceled'         => [],
    ];

    public function transition(Booking $booking, string $to): Booking
    {
        $from = (string) $booking->status;
        $to   = strtolower(trim($to));

        if (!array_key_exists($from, self::MAP) || !in_array($to, self::MAP[$from], true)) {
            throw ValidationException::withMessages([
                'to' => "Illegal transition: {$from} → {$to}"
            ]);
        }

        return DB::transaction(function () use ($booking, $to) {
            $now   = Carbon::now();
            $actor = Auth::id();

            $booking->status = $to;

            if (Schema::hasColumn('bookings', 'state_changed_at')) $booking->state_changed_at = $now;
            if (Schema::hasColumn('bookings', 'state_changed_by')) $booking->state_changed_by = $actor;

            if ($to === 'confirmed' && Schema::hasColumn('bookings', 'confirmed_at')) {
                $booking->confirmed_at = $now;
            }
            if ($to === 'completed' && Schema::hasColumn('bookings', 'completed_at')) {
                $booking->completed_at = $now;
            }
            if ($to === 'canceled') {
                // your DB uses U.S. spelling in ENUM
                if (Schema::hasColumn('bookings', 'cancelled_at')) $booking->cancelled_at = $now; // ok if you add this later
                if (Schema::hasColumn('bookings', 'canceled_at'))  $booking->canceled_at  = $now;
            }

            $booking->save();

            event(new BookingStatusUpdated($booking, $to));

            return $booking->fresh();
        });
    }
}
