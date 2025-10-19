<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = ['company_id','name','type','status','description','scheduled_at'];

    public function steps() { return $this->hasMany(CampaignStep::class)->orderBy('step_order'); }
    public function audiences() { return $this->hasMany(CampaignAudience::class); }
    public function triggers() { return $this->hasMany(Trigger::class); }
}
