<?php

namespace App\Models\Client;

use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientImportBatch extends Model
{
    protected $fillable = [
        'company_id',
        'uploaded_by',
        'original_filename',
        'stored_path',
        'status',
        'total_rows',
        'valid_rows',
        'warning_rows',
        'invalid_rows',
        'duplicate_rows',
        'suggested_retention_actions',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'total_rows' => 'integer',
        'valid_rows' => 'integer',
        'warning_rows' => 'integer',
        'invalid_rows' => 'integer',
        'duplicate_rows' => 'integer',
        'suggested_retention_actions' => 'integer',
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
        return $this->hasMany(ClientImportRow::class, 'batch_id')
            ->orderBy('row_number');
    }
}
