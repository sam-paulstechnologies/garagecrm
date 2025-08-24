<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Booking extends Model
{
    protected $table = 'bookings';

    protected $casts = [
        'pickup_required' => 'boolean',
        'is_archived'     => 'boolean',
    ];

    protected $fillable = [
        'company_id',
        'client_id',
        'opportunity_id',
        'vehicle_id',
        'name',
        'service_type',
        // one of these will exist in your schema:
        'date',
        'booking_date',
        'scheduled_at',
        // slot/time choice
        'slot',
        'assigned_to',
        'pickup_required',
        'pickup_address',
        'pickup_contact_number',
        'notes',
        'expected_duration',
        'expected_close_date',
        'priority',
        'status',        // ENUM of strings in your DB
        'is_archived',
    ];

    /* -------------------- Relationships -------------------- */

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Client\Client::class);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Client\Opportunity::class);
    }

    public function vehicleData(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Vehicle\Vehicle::class, 'vehicle_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_to');
    }

    /* -------------------- Availability Check -------------------- */
    /**
     * Schema-aware, parameterized slot availability check.
     *
     * @param  string    $bookingDate  Y-m-d (user-entered date)
     * @param  string    $slot         'morning' | 'afternoon' | 'evening' | 'full_day'
     * @param  int       $companyId
     * @param  int|null  $ignoreId     booking id to ignore (during edit)
     * @return bool                    true when slot is free
     */
    public static function isSlotAvailable(string $bookingDate, string $slot, int $companyId, ?int $ignoreId = null): bool
    {
        $q = static::query()
            ->where('company_id', $companyId)
            ->where('is_archived', false);

        if ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        }

        // Use whichever date-like column exists in your schema
        if (Schema::hasColumn('bookings', 'date')) {
            $q->whereDate('date', $bookingDate);
        } elseif (Schema::hasColumn('bookings', 'booking_date')) {
            $q->whereDate('booking_date', $bookingDate);
        } elseif (Schema::hasColumn('bookings', 'scheduled_at')) {
            $q->whereDate('scheduled_at', $bookingDate);
        }

        // Slot/time column
        if (Schema::hasColumn('bookings', 'slot')) {
            $q->where('slot', $slot);
        } elseif (Schema::hasColumn('bookings', 'booking_time')) {
            $q->where('booking_time', $slot);
        }

        // Ignore only cancelled/canceled bookings when checking availability
        // (Your 'status' column is an ENUM of strings.)
        if (Schema::hasColumn('bookings', 'status')) {
            $q->where(function ($qq) {
                $qq->whereNull('status')
                   ->orWhereNotIn('status', ['cancelled', 'canceled']);
            });
        }

        return !$q->exists();
    }
}
