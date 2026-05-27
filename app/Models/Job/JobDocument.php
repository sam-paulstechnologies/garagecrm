<?php

namespace App\Models\Job;

use App\Models\Client\Client;
use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobDocument extends Model
{
    protected $table = 'job_documents';

    protected $fillable = [
        'company_id',
        'client_id',
        'job_id',
        'type',
        'source',
        'sender_phone',
        'sender_email',
        'provider_message_id',
        'hash',
        'original_name',
        'mime',
        'size',
        'path',
        'url',
        'status',
        'received_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'job_id' => 'integer',
        'size' => 'integer',
        'received_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Route Model Binding Safety
    |--------------------------------------------------------------------------
    | Prevents /job-documents/{jobDocument} style access from resolving records
    | outside the authenticated user's company.
    |--------------------------------------------------------------------------
    */
    public function resolveRouteBinding($value, $field = null)
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        if (! $companyId) {
            return null;
        }

        return $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('company_id', $companyId)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    public function scopeNeedsReview($query)
    {
        return $query->where('status', 'needs_review');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }

    public function scopeMatched($query)
    {
        return $query->where('status', 'matched');
    }
}