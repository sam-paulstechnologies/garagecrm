<?php

namespace App\Models\Job;

use App\Models\Client\Client;
use App\Models\Client\Opportunity;
use App\Models\Traits\BelongsToCompany;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $table = 'bookings';

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_VEHICLE_RECEIVED = 'vehicle_received';
    public const STATUS_COMPLETED = 'completed';

    // DB status enum uses American spelling only: canceled
    public const STATUS_CANCELED = 'canceled';

    // Compatibility alias, but value is still DB-safe
    public const STATUS_CANCELLED = self::STATUS_CANCELED;

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
        'is_archived',

        'notes',

        'confirmed_at',
        'completed_at',

        // DB timestamp column uses British spelling
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
            ->where('status', '!=', self::STATUS_CANCELED);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return !$query->exists();
    }

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

            $label = trim(($make ?? '') . ' ' . ($model ?? ''));

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
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_VEHICLE_RECEIVED => 'Vehicle Received',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELED => 'Canceled',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }
}