<?php

namespace App\Models\Job;

use App\Models\Client\Client;
use App\Models\Client\Opportunity;
use App\Models\Traits\BelongsToCompany;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $table = 'bookings';

    /*
    |--------------------------------------------------------------------------
    | Booking Status Flow
    |--------------------------------------------------------------------------
    | Pending          = WhatsApp / AI / manager review needed
    | Scheduled        = Confirmed date + slot
    | Converted To Job = Vehicle received, job created
    | Lost             = Booking did not happen, reason required
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING = 'pending';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_CONVERTED_TO_JOB = 'converted_to_job';
    public const STATUS_LOST = 'lost';

    /*
    |--------------------------------------------------------------------------
    | Backward Compatibility Constants
    |--------------------------------------------------------------------------
    */

    public const STATUS_CONFIRMED = self::STATUS_SCHEDULED;
    public const STATUS_VEHICLE_RECEIVED = self::STATUS_CONVERTED_TO_JOB;
    public const STATUS_COMPLETED = self::STATUS_CONVERTED_TO_JOB;
    public const STATUS_CANCELED = self::STATUS_LOST;
    public const STATUS_CANCELLED = self::STATUS_LOST;

    public const LOST_REASON_CANCELLED_BY_CUSTOMER = 'cancelled_by_customer';
    public const LOST_REASON_REJECTED_BY_GARAGE = 'rejected_by_garage';
    public const LOST_REASON_NO_SHOW = 'no_show';
    public const LOST_REASON_SLOT_UNAVAILABLE = 'slot_unavailable';
    public const LOST_REASON_DUPLICATE = 'duplicate';
    public const LOST_REASON_WRONG_BOOKING = 'wrong_booking';
    public const LOST_REASON_PRICE_ISSUE = 'price_issue';
    public const LOST_REASON_CUSTOMER_POSTPONED = 'customer_postponed';
    public const LOST_REASON_OTHER = 'other';

    protected $fillable = [
        'company_id',
        'client_id',
        'vehicle_id',
        'opportunity_id',

        'name',
        'service_type',

        'booking_date',
        'slot',

        'assigned_to',

        'pickup_required',
        'pickup_address',
        'pickup_contact_number',

        'priority',
        'expected_duration',
        'expected_close_date',

        'status',
        'lost_reason',
        'is_archived',

        'notes',

        'confirmed_at',
        'completed_at',
        'cancelled_at',

        'state_changed_at',
        'state_changed_by',
        'reminder_sent_at',
    ];

    protected $casts = [
        'pickup_required' => 'boolean',
        'is_archived' => 'boolean',

        'booking_date' => 'date',
        'expected_close_date' => 'date',

        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'state_changed_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
    ];

    protected $with = [
        'client',
        'opportunity',
        'vehicleData',
        'assignedUser',
    ];

    /*
    |--------------------------------------------------------------------------
    | Route Model Binding Safety
    |--------------------------------------------------------------------------
    */

    public function resolveRouteBinding($value, $field = null)
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        if (! $companyId) {
            return null;
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('company_id', $companyId)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)
            ->withDefault(['name' => 'Unknown Client']);
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function vehicleData(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')
            ->withDefault(['name' => 'Unassigned']);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereDate('booking_date', '>=', now()->toDateString());
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeConvertedToJob($query)
    {
        return $query->where('status', self::STATUS_CONVERTED_TO_JOB);
    }

    public function scopeLost($query)
    {
        return $query->where('status', self::STATUS_LOST);
    }

    /*
    |--------------------------------------------------------------------------
    | Slot Availability
    |--------------------------------------------------------------------------
    */

    public static function isSlotAvailable(
        string $bookingDate,
        string $slot,
        int $companyId,
        ?int $ignoreId = null
    ): bool {
        $query = static::query()
            ->where('company_id', $companyId)
            ->where('is_archived', false)
            ->whereDate('booking_date', $bookingDate)
            ->where('slot', strtolower(trim($slot)))
            ->whereIn('status', [
                self::STATUS_PENDING,
                self::STATUS_SCHEDULED,
            ]);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return ! $query->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getSlotLabelAttribute(): string
    {
        return match (strtolower((string) $this->slot)) {
            'morning' => 'Morning',
            'afternoon' => 'Afternoon',
            'evening' => 'Evening',
            'full_day' => 'Full Day',
            default => ucfirst((string) $this->slot),
        };
    }

    public function getVehicleLabelAttribute(): ?string
    {
        if ($this->vehicleData) {
            $make = $this->vehicleData->make?->name;
            $model = $this->vehicleData->model?->name;
            $plate = $this->vehicleData->plate_number;

            $label = trim(($make ?? '') . ' ' . ($model ?? ''));

            if ($plate) {
                $label = trim($label . ' (' . $plate . ')');
            }

            if ($label !== '') {
                return $label;
            }
        }

        if ($this->opportunity?->vehicle_label) {
            return $this->opportunity->vehicle_label;
        }

        return null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match (strtolower((string) $this->status)) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_CONVERTED_TO_JOB => 'Converted To Job',
            self::STATUS_LOST => 'Lost Booking',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    public function getLostReasonLabelAttribute(): ?string
    {
        if (! $this->lost_reason) {
            return null;
        }

        return match ($this->lost_reason) {
            self::LOST_REASON_CANCELLED_BY_CUSTOMER => 'Cancelled by customer',
            self::LOST_REASON_REJECTED_BY_GARAGE => 'Rejected by garage',
            self::LOST_REASON_NO_SHOW => 'No show',
            self::LOST_REASON_SLOT_UNAVAILABLE => 'Slot unavailable',
            self::LOST_REASON_DUPLICATE => 'Duplicate',
            self::LOST_REASON_WRONG_BOOKING => 'Wrong booking',
            self::LOST_REASON_PRICE_ISSUE => 'Price issue',
            self::LOST_REASON_CUSTOMER_POSTPONED => 'Customer postponed',
            self::LOST_REASON_OTHER => 'Other',
            default => ucfirst(str_replace('_', ' ', (string) $this->lost_reason)),
        };
    }

    public function getIsActiveBookingAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_SCHEDULED,
        ], true);
    }
}