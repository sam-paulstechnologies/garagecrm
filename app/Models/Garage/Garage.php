<?php

namespace App\Models\Garage;

use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    protected $table = 'garages';

    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'email',
        'address',
        'is_default',
    ];

    protected $casts = [
        'company_id'  => 'integer',
        'is_default'  => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | ROUTE MODEL BINDING SAFETY
    |--------------------------------------------------------------------------
    */

    public function resolveRouteBinding($value, $field = null)
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        if (!$companyId) {
            return null;
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('company_id', $companyId)
            ->first();
    }

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}