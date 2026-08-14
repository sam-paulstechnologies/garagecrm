<?php

namespace App\Models\WhatsApp;

use App\Messaging\Models\MessagingConnection;
use App\Models\Client\Client;
use App\Models\Conversation;
use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class WhatsAppHistoryCandidate extends Model
{
    protected $table = 'whatsapp_history_candidates';

    public const CLASSIFICATIONS = ['likely_customer', 'possible_personal', 'possible_colleague', 'unknown'];

    public const RETENTION_LEVELS = ['high', 'medium', 'low', 'none'];

    public const DECISIONS = ['pending', 'track', 'dont_track'];

    protected $fillable = [
        'public_id', 'company_id', 'whatsapp_history_import_batch_id', 'messaging_connection_id',
        'external_identity_hash', 'phone_e164', 'display_name', 'message_count', 'inbound_count',
        'outbound_count', 'first_message_at', 'last_message_at', 'last_live_message_at',
        'analysis_fingerprint', 'intelligence_status', 'classification',
        'classification_confidence', 'classification_reason', 'retention_level',
        'retention_confidence', 'retention_reason', 'review_decision', 'reviewed_by',
        'analysis_requested_at', 'analysed_at', 'reviewed_at', 'imported_client_id',
        'imported_conversation_id', 'import_status', 'imported_at', 'staged_content_purged_at',
        'unsupported',
    ];

    protected $hidden = ['phone_e164', 'display_name', 'classification_reason', 'retention_reason'];

    protected $casts = [
        'phone_e164' => 'encrypted', 'display_name' => 'encrypted',
        'classification_reason' => 'encrypted', 'retention_reason' => 'encrypted',
        'classification_confidence' => 'decimal:4', 'retention_confidence' => 'decimal:4',
        'first_message_at' => 'datetime', 'last_message_at' => 'datetime',
        'last_live_message_at' => 'datetime', 'analysis_requested_at' => 'datetime',
        'analysed_at' => 'datetime', 'reviewed_at' => 'datetime', 'imported_at' => 'datetime',
        'staged_content_purged_at' => 'datetime', 'unsupported' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(fn (self $candidate) => $candidate->public_id ??= (string) Str::uuid());
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(WhatsAppHistoryImportBatch::class, 'whatsapp_history_import_batch_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(MessagingConnection::class, 'messaging_connection_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppHistoryMessage::class, 'whatsapp_history_candidate_id')
            ->orderBy('message_timestamp');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function importedClient(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'imported_client_id');
    }

    public function importedConversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'imported_conversation_id');
    }
}
