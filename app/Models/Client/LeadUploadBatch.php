<?php

namespace App\Models\Client;

use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadUploadBatch extends Model
{
    protected $fillable = [
        'company_id',
        'uploaded_by',
        'original_filename',
        'stored_path',
        'mode',
        'status',
        'total_rows',
        'valid_rows',
        'warning_rows',
        'invalid_rows',
        'duplicate_client_rows',
        'duplicate_lead_rows',
        'ready_ack_rows',
        'blocked_ack_rows',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'total_rows' => 'integer',
        'valid_rows' => 'integer',
        'warning_rows' => 'integer',
        'invalid_rows' => 'integer',
        'duplicate_client_rows' => 'integer',
        'duplicate_lead_rows' => 'integer',
        'ready_ack_rows' => 'integer',
        'blocked_ack_rows' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(LeadUploadRow::class, 'batch_id')
            ->orderBy('row_number');
    }
}
