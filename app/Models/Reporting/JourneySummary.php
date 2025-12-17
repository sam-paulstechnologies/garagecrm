<?php

namespace App\Models\Reporting;

use Illuminate\Database\Eloquent\Model;

class JourneySummary extends Model
{
    /**
     * This model maps to the SQL VIEW: vw_journey_summary
     * It is READ-ONLY (no timestamps, no fillable restrictions).
     */
    protected $table = 'vw_journey_summary';

    public $timestamps = false;

    protected $guarded = [];
}
