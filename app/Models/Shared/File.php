<?php

namespace App\Models\Shared;

use App\Models\Client\Client;
use App\Models\Job\Job;
use App\Models\Job\Invoice;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'files';

    protected $fillable = [
        'company_id',

        'client_id',
        'booking_id',
        'job_id',
        'invoice_id',

        'file_name',
        'file_path',
        'file_type',
        'category',

        'uploaded_by',
        'notes',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    /* ================= Relationships ================= */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')
            ->withDefault(['name' => 'System']);
    }

    /* ================= Scopes ================= */

    public function scopeForCompany($q, int $companyId)
    {
        return $q->where('company_id', $companyId);
    }

    public function scopeRecent($q, int $limit = 10)
    {
        return $q->orderByDesc('uploaded_at')->limit($limit);
    }
}
