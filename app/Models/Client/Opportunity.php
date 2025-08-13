<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;

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

    // 🔗 Relationships
    public function client()         { return $this->belongsTo(Client::class); }
    public function lead()           { return $this->belongsTo(Lead::class); }
    public function assignedUser()   { return $this->belongsTo(User::class, 'assigned_to'); }
    public function vehicle()        { return $this->belongsTo(Vehicle::class); }
    public function vehicleMake()    { return $this->belongsTo(VehicleMake::class, 'vehicle_make_id'); }
    public function vehicleModel()   { return $this->belongsTo(VehicleModel::class, 'vehicle_model_id'); }

    // 📌 Accessors & Mutators
    public function getServiceTypeArrayAttribute()
    {
        return explode(',', $this->service_type ?? '');
    }

    public function setServiceTypeArrayAttribute($value)
    {
        $this->attributes['service_type'] = is_array($value) ? implode(',', $value) : $value;
    }

    // 🔍 Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // 🚗 Optional: fallback to lead vehicle if set
    public function getDefaultVehicleAttribute()
    {
        return $this->lead?->vehicle ?? null;
    }
}
