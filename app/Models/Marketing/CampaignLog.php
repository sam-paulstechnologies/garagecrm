<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class CampaignLog extends Model
{
    protected $fillable = ['company_id','campaign_id','enrollment_id','level','message','context'];
    protected $casts = ['context'=>'array'];
}
