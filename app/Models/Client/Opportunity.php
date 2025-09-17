<?php

namespace App\Models\Client;

use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opportunity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'lead_id',
        'company_id',
        'title',
        'service_type',
        'stage',
        'value',
        'expected_close_date',
        'notes',
        'source',
        'assigned_to',
        'priority',
        'is_converted',
        'close_reason',
        'next_follow_up',
        'expected_duration',
        'score',
        'vehicle_id',
        'vehicle_make_id',
        'vehicle_model_id',
        'other_make',
        'other_model',
    ];

    /* -------------------------
     | Relationships
     ------------------------- */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** Primary accessor used in controllers/partials */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')
            ->withDefault(['name' => 'Unassigned']);
    }

    /** Alias for UI that references owner() */
    public function owner(): BelongsTo
    {
        return $this->assignee();
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function vehicleMake(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'vehicle_make_id');
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    /* -------------------------
     | Accessors & Mutators
     ------------------------- */
    public function getServiceTypeArrayAttribute(): array
    {
        return explode(',', $this->service_type ?? '');
    }

    public function setServiceTypeArrayAttribute($value): void
    {
        $this->attributes['service_type'] = is_array($value) ? implode(',', $value) : $value;
    }

    /* -------------------------
     | Scopes
     ------------------------- */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /* -------------------------
     | Convenience
     ------------------------- */
    /** Optional: fallback to lead vehicle if set */
    public function getDefaultVehicleAttribute()
    {
        return $this->lead?->vehicle ?? null;
    }
}
