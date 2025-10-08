<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;

class JobCard extends Model
{
    protected $table = 'job_cards';

    protected $fillable = [
        'job_id','description','status','file_path','file_type','extracted_text',
        'assigned_to','completed_at','company_id',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function job() { return $this->belongsTo(Job::class); }
}
