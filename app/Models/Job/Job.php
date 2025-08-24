<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToCompany;
use App\Models\Client\Client;
use App\Models\User;

class Job extends Model
{
    use HasFactory, BelongsToCompany, SoftDeletes;

    protected $table = 'jobs';

    // Match your DB (DESCRIBE jobs screenshot)
    protected $fillable = [
        'company_id',
        'booking_id',
        'client_id',
        'job_code',
        'start_time',
        'end_time',
        'description',
        'work_summary',
        'issues_found',
        'parts_used',
        'total_time_minutes',
        'is_archived',
        'status',          // enum: pending,in_progress,completed
        'assigned_to',
    ];

    protected $casts = [
        'start_time'         => 'datetime',  // your table has start_time
        'end_time'           => 'datetime',  // your table has end_time
        'is_archived'        => 'boolean',
        'total_time_minutes' => 'integer',
    ];

    public function client()       { return $this->belongsTo(Client::class); }
    public function assignedUser() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function booking()      { return $this->belongsTo(Booking::class); }
    public function invoice()      { return $this->hasOne(Invoice::class); }
}
