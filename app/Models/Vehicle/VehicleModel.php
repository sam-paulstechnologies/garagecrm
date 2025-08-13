<?php
namespace App\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleModel extends Model
{
    protected  = ['make_id','name'];
    public  = false;

    public function make(): BelongsTo
    {
        return ->belongsTo(VehicleMake::class, 'make_id');
    }
}
