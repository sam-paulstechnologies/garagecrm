<?php
namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    protected $fillable = [
        "company_id",
        "client_id",
        "make_id",
        "model_id",
        "plate_number",
        "year",
        "color",
        "registration_expiry_date",
        "insurance_expiry_date"
    ];

    public function make(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, "make_id");
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, "model_id");
    }
}