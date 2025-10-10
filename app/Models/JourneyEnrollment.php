<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourneyEnrollment extends Model
{
    protected $fillable = [
        'company_id','journey_id','enrollable_type','enrollable_id',
        'current_step_position','status','context'
    ];
    protected $casts = ['context'=>'array'];

    public function journey(){ return $this->belongsTo(Journey::class); }
    public function enrollable(){ return $this->morphTo(); }
}
