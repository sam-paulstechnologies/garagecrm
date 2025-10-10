<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'company_id','name','channel','message_template_id','scheduled_at','status'
    ];
    protected $casts = ['scheduled_at'=>'datetime'];

    public function template(){ return $this->belongsTo(MessageTemplate::class,'message_template_id'); }
    public function audience(){ return $this->hasMany(CampaignAudience::class); }
}
