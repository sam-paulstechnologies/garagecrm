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

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}