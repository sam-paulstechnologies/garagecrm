<?php

namespace App\Models\QuickScan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class QuickScanCandidate extends Model
{
    protected $fillable = [
        'public_id', 'quick_scan_workspace_id', 'external_identity_hash', 'customer_identifier',
        'display_name', 'message_count', 'inbound_count', 'outbound_count', 'first_message_at',
        'last_message_at', 'deterministic_excluded', 'intelligence_status', 'analysis_fingerprint',
        'classification', 'classification_confidence', 'classification_reason', 'retention_level',
        'retention_confidence', 'retention_reason', 'potential_missed', 'quote_unresolved',
        'service_related', 'analysis_counted_at', 'analysed_at', 'purged_at',
    ];

    protected $hidden = ['customer_identifier', 'display_name', 'classification_reason', 'retention_reason'];

    protected $casts = [
        'customer_identifier' => 'encrypted', 'display_name' => 'encrypted',
        'classification_reason' => 'encrypted', 'retention_reason' => 'encrypted',
        'deterministic_excluded' => 'boolean', 'potential_missed' => 'boolean',
        'quote_unresolved' => 'boolean', 'service_related' => 'boolean',
        'classification_confidence' => 'decimal:4', 'retention_confidence' => 'decimal:4',
        'first_message_at' => 'datetime', 'last_message_at' => 'datetime',
        'analysis_counted_at' => 'datetime', 'analysed_at' => 'datetime', 'purged_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $candidate) => $candidate->public_id ??= (string) Str::uuid());
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(QuickScanWorkspace::class, 'quick_scan_workspace_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(QuickScanMessage::class)->orderBy('message_timestamp');
    }
}
