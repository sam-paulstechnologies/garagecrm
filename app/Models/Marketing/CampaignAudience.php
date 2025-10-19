<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class CampaignAudience extends Model
{
    protected $fillable = ['campaign_id','audiencable_type','audiencable_id','filters'];
    protected $casts = ['filters'=>'array'];
    public function campaign(){ return $this->belongsTo(Campaign::class); }
}
