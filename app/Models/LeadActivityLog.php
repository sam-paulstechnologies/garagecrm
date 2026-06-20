<?php

namespace App\Models;

use App\Models\Client\Lead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivityLog extends Model
{
    protected $fillable = [
        'company_id',
        'lead_id',
        'user_id',
        'action',
        'field',
        'old_value',
        'new_value',
        'source',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
