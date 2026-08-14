<?php

namespace App\Models\WhatsApp;

use App\Messaging\Models\MessagingConnection;
use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WhatsAppHistoryImportBatch extends Model
{
    protected $table = 'whatsapp_history_import_batches';

    protected $fillable = [
        'public_id', 'company_id', 'messaging_connection_id', 'connection_scope_hash', 'status',
        'contacts_discovered', 'contacts_eligible', 'contacts_analysed', 'pending_review',
        'track_selected', 'dont_track_selected', 'new_clients', 'matched_clients',
        'messages_imported', 'excluded_contacts', 'failed_records', 'last_error_code',
        'sync_started_at', 'sync_completed_at', 'review_expires_at', 'completed_at',
    ];

    protected $casts = [
        'sync_started_at' => 'datetime', 'sync_completed_at' => 'datetime',
        'review_expires_at' => 'datetime', 'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $batch) => $batch->public_id ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(MessagingConnection::class, 'messaging_connection_id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(WhatsAppHistoryCandidate::class, 'whatsapp_history_import_batch_id');
    }
}
