<?php

namespace App\Models\QuickScan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class QuickScanProviderSession extends Model
{
    protected $fillable = [
        'public_id', 'quick_scan_workspace_id', 'state_hash', 'state_encrypted',
        'connection_mode', 'status', 'access_token', 'waba_id', 'phone_number_id',
        'business_id', 'display_phone_number', 'waba_hash', 'phone_number_hash',
        'expires_at', 'completed_at', 'sync_requested_at', 'last_webhook_at', 'error_code',
    ];

    protected $hidden = [
        'state_hash', 'state_encrypted', 'access_token', 'waba_id', 'phone_number_id',
        'business_id', 'display_phone_number',
    ];

    protected $casts = [
        'state_encrypted' => 'encrypted', 'access_token' => 'encrypted', 'waba_id' => 'encrypted',
        'phone_number_id' => 'encrypted', 'business_id' => 'encrypted',
        'display_phone_number' => 'encrypted', 'expires_at' => 'datetime',
        'completed_at' => 'datetime', 'sync_requested_at' => 'datetime', 'last_webhook_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $session) => $session->public_id ??= (string) Str::uuid());
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(QuickScanWorkspace::class, 'quick_scan_workspace_id');
    }
}
