<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToCompany;
use App\Models\Job\Job;

class JobCard extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'job_id',
        'description',
        'status',
        'assigned_to',
        'completed_at',
        'company_id',
    ];

    // 🔗 Relationships
    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
