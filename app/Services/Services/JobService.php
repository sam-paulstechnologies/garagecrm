<?php

namespace App\Models\Service;

use Illuminate\Database\Eloquent\Model;
use App\Models\Job\Job;

class JobService extends Model
{
    protected $fillable = [
        'job_id',
        'service_type_id',
        'override_trt_days',
        'next_followup_date',
        'status'
    ];

    protected $casts = [
        'next_followup_date' => 'date'
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }
}