<?php

namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Client\Client;

class Vehicle extends Model
{
    protected $fillable = [
        'company_id',
        'client_id',
        'make_id',
        'model_id',
        'plate_number',
        'vin',
        'year',
        'color',
        'registration_expiry_date',
        'insurance_expiry_date',
        'last_inspection_date',
        'inspection_expiry_date',
        'current_mileage'
    ];

    protected $casts = [
        'registration_expiry_date' => 'date',
        'insurance_expiry_date'    => 'date',
        'last_inspection_date'     => 'date',
        'inspection_expiry_date'   => 'date',
        'current_mileage'          => 'integer'
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'model_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getLabelAttribute(): ?string
    {
        $make  = $this->make?->name;
        $model = $this->model?->name;

        return trim(($make ?? '') . ' ' . ($model ?? '')) ?: null;
    }

    /*
    |--------------------------------------------------------------------------
    | Scope: Find vehicle for same client + make + model
    |--------------------------------------------------------------------------
    */

    public function scopeSameVehicle(
        Builder $query,
        int $companyId,
        int $clientId,
        int $makeId,
        int $modelId
    ): Builder {

        return $query
            ->where('company_id', $companyId)
            ->where('client_id', $clientId)
            ->where('make_id', $makeId)
            ->where('model_id', $modelId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Find or create vehicle (prevents duplicates)
    |--------------------------------------------------------------------------
    */

    public static function findOrCreateVehicle(
        int $companyId,
        int $clientId,
        int $makeId,
        int $modelId
    ): self {

        $vehicle = static::sameVehicle(
            $companyId,
            $clientId,
            $makeId,
            $modelId
        )->first();

        if ($vehicle) {
            return $vehicle;
        }

        return static::create([
            'company_id' => $companyId,
            'client_id'  => $clientId,
            'make_id'    => $makeId,
            'model_id'   => $modelId,
        ]);
    }
}