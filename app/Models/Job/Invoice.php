<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToCompany;
use App\Models\Client\Client;
use App\Models\Job\Job;
use Illuminate\Support\Facades\Storage;

class Invoice extends Model
{
    use HasFactory, BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'client_id',
        'job_id',

        // agreed additions
        'source',         // 'generated' | 'upload'
        'is_primary',     // bool
        'number',
        'invoice_date',
        'currency',

        // existing fields you already use
        'amount',
        'status',         // enum: pending, paid, overdue
        'due_date',

        // file + dedupe
        'file_path',
        'url',
        'file_type',
        'mime',
        'size',
        'hash',
        'version',
        'uploaded_by',

        'extracted_text', // for OCR later
    ];

    protected $casts = [
        'is_primary'   => 'boolean',
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'amount'       => 'decimal:2',
    ];

    /* ---------------- Relations ---------------- */

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    /* ---------------- Scopes ---------------- */

    public function scopeForJob($q, $jobId)
    {
        return $q->where('job_id', $jobId);
    }

    public function scopePrimary($q)
    {
        return $q->where('is_primary', true);
    }

    /* ---------------- Accessors ---------------- */

    public function getResolvedUrlAttribute(): ?string
    {
        if ($this->url) {
            return $this->url;
        }
        if ($this->file_path) {
            return Storage::disk('public')->url($this->file_path);
        }
        return null;
    }
}
