<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Model;

class CampaignEnrollment extends Model
{
    protected $table = 'campaign_enrollments';

    protected $guarded = [];

    /**
     * Campaign relationship
     */
    public function campaign()
    {
        return $this->belongsTo(\App\Models\Marketing\Campaign::class, 'campaign_id');
    }

    /**
     * Lead relationship
     */
    public function lead()
    {
        return $this->belongsTo(\App\Models\Client\Lead::class, 'lead_id');
    }
}