<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class Trigger extends Model
{
    protected $table = 'marketing_triggers';

    protected $fillable = [
        'company_id','name','event','conditions','campaign_id','status'
    ];

    protected $casts = [
        'conditions' => 'array',
    ];

    public function campaign() {
        return $this->belongsTo(Campaign::class);
    }
}
