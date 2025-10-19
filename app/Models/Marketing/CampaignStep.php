<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class CampaignStep extends Model
{
    protected $fillable = ['campaign_id','step_order','action','template_id','action_params'];
    protected $casts = ['action_params' => 'array'];
    public function campaign(){ return $this->belongsTo(Campaign::class); }
}
