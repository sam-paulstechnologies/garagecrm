<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Traits\BelongsToCompany;
use App\Models\Client\Client;
use App\Models\User;
use App\Models\Job\Invoice;
use App\Models\Job\JobCard;
use App\Models\Job\JobDocument;
use App\Models\Service\JobService;

class Job extends Model
{
    use HasFactory, BelongsToCompany, SoftDeletes;

    protected $table = 'jobs';

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
        'status',
        'assigned_to',
    ];

    protected $casts = [
        'start_time'         => 'datetime',
        'end_time'           => 'datetime',
        'is_archived'        => 'boolean',
        'total_time_minutes' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Auto Job Code Generator
    |--------------------------------------------------------------------------
    */

    protected static function booted()
    {
        static::creating(function ($job) {

            if (!$job->job_code) {

                $lastId = static::where('company_id', $job->company_id)
                    ->max('id');

                $next = str_pad(($lastId ?? 0) + 1, 5, '0', STR_PAD_LEFT);

                $job->job_code = 'JOB-'.$next;
            }

            if (!$job->status) {
                $job->status = 'pending';
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function booking()
    {
        return $this->belongsTo(\App\Models\Job\Booking::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'job_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'job_id');
    }

    public function primaryInvoice()
    {
        return $this->hasOne(Invoice::class, 'job_id')
            ->where('is_primary', true);
    }

    public function jobCards()
    {
        return $this->hasMany(JobCard::class, 'job_id');
    }

    public function jobDocuments()
    {
        return $this->hasMany(JobDocument::class, 'job_id');
    }

    /*
    |--------------------------------------------------------------------------
    | NEW: Services performed in this job
    |--------------------------------------------------------------------------
    */

    public function services()
    {
        return $this->hasMany(JobService::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function primaryInvoiceUrl(): ?string
    {
        return optional($this->primaryInvoice)->url ?? null;
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function calculateDuration(): ?int
    {
        if ($this->start_time && $this->end_time) {
            return $this->start_time->diffInMinutes($this->end_time);
        }

        return null;
    }
}