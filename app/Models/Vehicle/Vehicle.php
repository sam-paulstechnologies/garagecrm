<?php

namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client\Client;
use App\Models\Vehicle\VehicleModel;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;

class Vehicle extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'vehicle_model_id',
        'trim',
        'plate_number',
        'year',
        'color',
        'registration_expiry_date',
        'insurance_expiry_date',
    ];

    // 🔗 Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function model()
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    public function vehicleMake()
    {
        return $this->model?->make;
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // 🎯 Accessors
    public function getMakeNameAttribute(): ?string
    {
        return $this->vehicleMake()?->name;
    }

    public function getModelNameAttribute(): ?string
    {
        return $this->model?->name;
    }

    public function getDisplayNameAttribute(): string
    {
        return trim("{$this->make_name} {$this->model_name} {$this->trim}");
    }
}
