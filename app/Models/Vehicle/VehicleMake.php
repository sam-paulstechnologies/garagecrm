<?php
namespace App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleMake extends Model
{
    protected  = ['name'];
    public  = false;

    public function models(): HasMany
    {
        return ->hasMany(VehicleModel::class, 'make_id');
    }
}
