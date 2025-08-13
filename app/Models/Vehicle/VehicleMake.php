<?php

namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleMake extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // 🔗 Relationships
    public function models()
    {
        return $this->hasMany(VehicleModel::class, 'make_id');
    }
}
