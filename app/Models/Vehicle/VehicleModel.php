<?php

namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'make_id',
        'name',
    ];

    // 🔗 Relationships
    public function make()
    {
        return $this->belongsTo(VehicleMake::class, 'make_id');
    }
}
