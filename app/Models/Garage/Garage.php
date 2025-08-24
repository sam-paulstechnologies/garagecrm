<?php

namespace App\Models\Garage;

use Illuminate\Database\Eloquent\Model;

class Garage extends Model
{
    protected $fillable = ['company_id','name','phone','email','address','is_default'];

    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class);
    }
}
