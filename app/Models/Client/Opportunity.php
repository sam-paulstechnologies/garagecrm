<?php

namespace App\Models\Client;

use App\Models\Job\Booking;
use App\Models\Job\Invoice;
use App\Models\Job\Job;
use App\Models\Traits\BelongsToCompany;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $table = 'opportunities';

    public const STAGE_NEW = 'new';
    public const STAGE_ATTEMPTING_CONTACT = 'attempting_contact';
    public const STAGE_COLLECTING_DETAILS = 'collecting_details';
    public const STAGE_MANAGER_CONFIRMATION_PENDING = 'manager_confirmation_pending';
    public const STAGE_APPOINTMENT = 'appointment';
    public const STAGE_OFFER = 'offer';
    public const STAGE_CLOSED_WON = 'closed_won';
    public const STAGE_CLOSED_LOST = 'closed_lost';

    public const STAGES = [
        self::STAGE_NEW,
        self::STAGE_ATTEMPTING_CONTACT,
        self::STAGE_COLLECTING_DETAILS,
        self::STAGE_MANAGER_CONFIRMATION_PENDING,
        self::STAGE_APPOINTMENT,
        self::STAGE_OFFER,
        self::STAGE_CLOSED_WON,
        self::STAGE_CLOSED_LOST,
    ];

    public const ACTIVE_STAGES = [
        self::STAGE_NEW,
        self::STAGE_ATTEMPTING_CONTACT,
        self::STAGE_COLLECTING_DETAILS,
        self::STAGE_MANAGER_CONFIRMATION_PENDING,
        self::STAGE_APPOINTMENT,
        self::STAGE_OFFER,
    ];

    protected $fillable = [
        'client_id',
        'lead_id',
        'company_id',

        'title',
        'service_type',
        'notes',
        'source',

        'stage',
        'priority',
        'value',
        'expected_close_date',
        'is_converted',
        'is_archived',
        'close_reason',

        'ai_status',

        'next_follow_up',
        'expected_duration',
        'score',

        'assigned_to',

        'vehicle_id',
        'vehicle_make_id',
        'vehicle_model_id',
        'other_make',
        'other_model',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_converted' => 'boolean',
        'is_archived' => 'boolean',
        'expected_close_date' => 'date',
        'next_follow_up' => 'date',
        'score' => 'integer',
    ];

    protected $with = [
        'client',
        'assignee',
        'vehicle',
        'vehicleMake',
        'vehicleModel',
    ];

    /*
    |--------------------------------------------------------------------------
    | ROUTE MODEL BINDING SAFETY
    |--------------------------------------------------------------------------
    */

    public function resolveRouteBinding($value, $field = null)
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        if (!$companyId) {
            return null;
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('company_id', $companyId)
            ->first();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')
            ->withDefault([
                'name' => 'Unassigned',
            ]);
    }

    public function owner(): BelongsTo
    {
        return $this->assignee();
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function vehicleMake(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'vehicle_make_id');
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'opportunity_id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class, 'opportunity_id');
    }

    public function invoices()
    {
        return $this->hasManyThrough(
            Invoice::class,
            Job::class,
            'opportunity_id',
            'job_id',
            'id',
            'id'
        );
    }

    public function getVehicleLabelAttribute(): ?string
    {
        if ($this->vehicle) {
            $make = $this->vehicle->make?->name;
            $model = $this->vehicle->model?->name;

            $label = trim(($make ?? '') . ' ' . ($model ?? ''));

            if ($label !== '') {
                return $label;
            }
        }

        $make = $this->vehicleMake?->name ?? $this->other_make;
        $model = $this->vehicleModel?->name ?? $this->other_model;

        $label = trim(($make ?? '') . ' ' . ($model ?? ''));

        return $label !== '' ? $label : null;
    }

    public function getStageLabelAttribute(): string
    {
        return match ((string) $this->stage) {
            self::STAGE_NEW => 'New',
            self::STAGE_ATTEMPTING_CONTACT => 'Attempting Contact',
            self::STAGE_COLLECTING_DETAILS => 'Collecting Details',
            self::STAGE_MANAGER_CONFIRMATION_PENDING => 'Manager Confirmation Pending',
            self::STAGE_APPOINTMENT => 'Appointment',
            self::STAGE_OFFER => 'Offer',
            self::STAGE_CLOSED_WON => 'Closed Won',
            self::STAGE_CLOSED_LOST => 'Closed Lost',
            default => ucfirst(str_replace('_', ' ', (string) $this->stage)),
        };
    }

    public function markCollectingDetails(): void
    {
        $this->update([
            'stage' => self::STAGE_COLLECTING_DETAILS,
        ]);
    }

    public function markManagerConfirmation(): void
    {
        $this->update([
            'stage' => self::STAGE_MANAGER_CONFIRMATION_PENDING,
        ]);
    }

    public function markAppointmentBooked(): void
    {
        $this->update([
            'stage' => self::STAGE_APPOINTMENT,
        ]);
    }

    public function markClosedWon(): void
    {
        $this->update([
            'stage' => self::STAGE_CLOSED_WON,
            'is_converted' => true,
        ]);
    }

    public function markClosedLost(?string $reason = null): void
    {
        $this->update([
            'stage' => self::STAGE_CLOSED_LOST,
            'close_reason' => $reason,
            'is_converted' => false,
        ]);
    }

    public function scopePendingManager($query)
    {
        return $query->where('stage', self::STAGE_MANAGER_CONFIRMATION_PENDING);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('stage', self::ACTIVE_STAGES);
    }
}