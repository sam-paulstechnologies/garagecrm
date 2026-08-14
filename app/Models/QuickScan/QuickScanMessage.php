<?php

namespace App\Models\QuickScan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickScanMessage extends Model
{
    protected $fillable = [
        'quick_scan_workspace_id', 'quick_scan_candidate_id', 'source_fingerprint',
        'provider_message_hash', 'direction', 'message_type', 'body', 'metadata',
        'message_timestamp', 'purged_at',
    ];

    protected $hidden = ['body', 'metadata'];

    protected $casts = [
        'body' => 'encrypted', 'metadata' => 'encrypted:array',
        'message_timestamp' => 'datetime', 'purged_at' => 'datetime',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(QuickScanWorkspace::class, 'quick_scan_workspace_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(QuickScanCandidate::class, 'quick_scan_candidate_id');
    }
}
