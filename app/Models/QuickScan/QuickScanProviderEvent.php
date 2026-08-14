<?php

namespace App\Models\QuickScan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickScanProviderEvent extends Model
{
    protected $fillable = [
        'quick_scan_workspace_id', 'quick_scan_provider_session_id', 'event_key', 'field',
        'provider_event_hash', 'payload_hash', 'payload', 'status', 'error_code',
        'occurred_at', 'processed_at',
    ];

    protected $hidden = ['payload'];

    protected $casts = ['payload' => 'encrypted:array', 'occurred_at' => 'datetime', 'processed_at' => 'datetime'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(QuickScanWorkspace::class, 'quick_scan_workspace_id');
    }
}
