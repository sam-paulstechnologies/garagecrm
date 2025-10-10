<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Journey extends Model
{
    protected $fillable = ['company_id','name','trigger','is_active'];
    protected $casts = ['is_active'=>'boolean'];

    public function steps(){ return $this->hasMany(JourneyStep::class)->orderBy('position'); }
}
