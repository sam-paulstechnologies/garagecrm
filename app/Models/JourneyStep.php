<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourneyStep extends Model
{
    protected $fillable = ['journey_id','position','type','config'];
    protected $casts = ['config'=>'array'];

    public function journey(){ return $this->belongsTo(Journey::class); }
}
