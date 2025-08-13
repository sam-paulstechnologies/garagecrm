<?php
namespace App\Models\Vehicle;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleMake extends Model
{
    protected $fillable = ['name'];
    public $timestamps = false;

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class, 'make_id');
    }
}