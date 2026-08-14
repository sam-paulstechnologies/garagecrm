<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Model;

class WhatsAppHistoryMessage extends Model
{
    protected $table = 'whatsapp_history_messages';

    protected $fillable = [
        'company_id',
        'whatsapp_history_import_batch_id',
        'whatsapp_history_candidate_id',
        'phone_number_id',
        'external_identity_hash',
        'source_fingerprint',
        'provider_message_id',
        'direction',
        'message_type',
        'source',
        'customer_identifier',
        'body',
        'metadata',
        'message_timestamp',
        'purged_at',
    ];

    protected $hidden = ['customer_identifier', 'body', 'metadata'];

    protected $casts = [
        'customer_identifier' => 'encrypted',
        'body' => 'encrypted',
        'metadata' => 'encrypted:array',
        'message_timestamp' => 'datetime',
        'purged_at' => 'datetime',
    ];

    public function candidate()
    {
        return $this->belongsTo(WhatsAppHistoryCandidate::class, 'whatsapp_history_candidate_id');
    }
}
