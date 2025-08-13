<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;
use App\Models\Client\Client;
use App\Models\Client\Opportunity;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use App\Models\Job\Job;

class Booking extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'client_id',
        'opportunity_id',
        'vehicle_id',
        'vehicle_make_id',
        'vehicle_model_id',
        'other_make',
        'other_model',
        'name',
        'service_type',
        'date',
        'slot',
        'assigned_to',
        'pickup_required',
        'pickup_address',
        'pickup_contact_number',
        'status',
        'notes',
        'company_id',
        'source',
        'priority',
        'expected_duration',
        'expected_close_date',
        'is_archived',
    ];

    protected $casts = [
        'pickup_required'      => 'boolean',
        'date'                 => 'date',
        'expected_close_date'  => 'date',
    ];

    // 🔗 Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function vehicleData()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function job()
    {
        return $this->hasOne(Job::class);
    }

    public function vehicleMake()
    {
        return $this->belongsTo(VehicleMake::class, 'vehicle_make_id');
    }

    public function vehicleModel()
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    // ✅ Slot availability checker
    public static function isSlotAvailable($date, $slot, $companyId, $excludeId = null)
    {
        return !self::where('date', $date)
            ->where('slot', $slot)
            ->where('company_id', $companyId)
            ->where('status', '!=', 'cancelled')
            ->where('is_archived', false)
            ->when($excludeId, fn($query) => $query->where('id', '!=', $excludeId))
            ->exists();
    }
}
