<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Model;

class WhatsAppHistoryMessage extends Model
{
    protected $table = 'whatsapp_history_messages';

    protected $fillable = [
        'company_id',
        'phone_number_id',
        'source_fingerprint',
        'provider_message_id',
        'direction',
        'message_type',
        'customer_identifier',
        'body',
        'metadata',
        'message_timestamp',
    ];

    protected $hidden = ['customer_identifier', 'body', 'metadata'];

    protected $casts = [
        'customer_identifier' => 'encrypted',
        'body' => 'encrypted',
        'metadata' => 'encrypted:array',
        'message_timestamp' => 'datetime',
    ];
}
