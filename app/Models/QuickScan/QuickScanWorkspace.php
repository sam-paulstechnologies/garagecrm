<?php

namespace App\Models\QuickScan;

use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class QuickScanWorkspace extends Model
{
    public const STATUSES = [
        'created', 'consent_pending', 'connection_pending', 'history_syncing', 'analysing',
        'report_ready', 'accepted', 'declined', 'expired', 'purging', 'purged', 'failed',
    ];

    protected $fillable = [
        'public_id', 'created_by', 'owner_user_id', 'converted_company_id', 'garage_name',
        'garage_contact_name', 'garage_phone', 'garage_email', 'notes', 'garage_identity_hash',
        'source', 'follow_up_at', 'outcome', 'status', 'access_token_hash',
        'access_token_encrypted', 'link_expires_at', 'report_expires_at', 'revoked_at',
        'opened_at', 'consent_at', 'consent_policy_version', 'connection_mode',
        'claimed_whatsapp_number', 'claimed_whatsapp_hash', 'staff_number_hashes',
        'analysis_limit', 'contacts_discovered', 'contacts_deterministic_excluded',
        'contacts_analysed', 'ai_calls', 'analysis_duration_ms', 'report_metrics',
        'connection_started_at', 'connected_at', 'history_sync_started_at',
        'history_sync_completed_at', 'analysis_started_at', 'analysis_completed_at',
        'report_ready_at', 'report_viewed_at', 'accepted_at', 'declined_at',
        'purge_scheduled_at', 'purged_at', 'failure_code', 'failure_detail',
    ];

    protected $hidden = [
        'access_token_hash', 'access_token_encrypted', 'garage_contact_name', 'garage_phone',
        'garage_email', 'notes', 'claimed_whatsapp_number', 'staff_number_hashes', 'failure_detail',
    ];

    protected $casts = [
        'garage_name' => 'encrypted', 'garage_contact_name' => 'encrypted',
        'garage_phone' => 'encrypted', 'garage_email' => 'encrypted', 'notes' => 'encrypted',
        'access_token_encrypted' => 'encrypted', 'claimed_whatsapp_number' => 'encrypted',
        'failure_detail' => 'encrypted', 'staff_number_hashes' => 'array', 'report_metrics' => 'array',
        'follow_up_at' => 'date', 'link_expires_at' => 'datetime', 'report_expires_at' => 'datetime',
        'revoked_at' => 'datetime', 'opened_at' => 'datetime', 'consent_at' => 'datetime',
        'connection_started_at' => 'datetime', 'connected_at' => 'datetime',
        'history_sync_started_at' => 'datetime', 'history_sync_completed_at' => 'datetime',
        'analysis_started_at' => 'datetime', 'analysis_completed_at' => 'datetime',
        'report_ready_at' => 'datetime', 'report_viewed_at' => 'datetime',
        'accepted_at' => 'datetime', 'declined_at' => 'datetime',
        'purge_scheduled_at' => 'datetime', 'purged_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $scan) => $scan->public_id ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(QuickScanCandidate::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(QuickScanMessage::class);
    }

    public function providerSessions(): HasMany
    {
        return $this->hasMany(QuickScanProviderSession::class);
    }

    public function providerEvents(): HasMany
    {
        return $this->hasMany(QuickScanProviderEvent::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(QuickScanEvent::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function convertedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'converted_company_id');
    }
}
