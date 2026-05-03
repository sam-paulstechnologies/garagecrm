<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Client\Client;
use App\Models\User;

class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'invoices';

    /**
     * Mass assignable columns
     * (STRICTLY matches DB schema)
     */
    protected $fillable = [
        'company_id',
        'client_id',
        'job_id',

        'source',        // enum: generated | upload

        'file_path',
        'url',
        'file_type',
        'mime',
        'size',
        'hash',
        'version',
        'uploaded_by',
        'extracted_text',

        'amount',
        'status',        // pending | paid | overdue
        'is_primary',

        'number',
        'invoice_date',
        'currency',
        'due_date',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'is_primary'   => 'boolean',
        'amount'       => 'decimal:2',
        'size'         => 'integer',
        'version'      => 'integer',
    ];

    /* =====================================================
     | Relationships
     ===================================================== */

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /* =====================================================
     | Helpers / Computed
     ===================================================== */

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'overdue';
    }

    public function isGenerated(): bool
    {
        return $this->source === 'generated';
    }

    public function isUploaded(): bool
    {
        return $this->source === 'upload';
    }

    public function downloadUrl(): ?string
    {
        return $this->file_path
            ? asset('storage/'.$this->file_path)
            : null;
    }
}
