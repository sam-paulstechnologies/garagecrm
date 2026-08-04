<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Model;

class WhatsAppWebhookEvent extends Model
{
    protected $table = 'whatsapp_webhook_events';

    protected $fillable = [
        'company_id',
        'event_key',
        'field',
        'event_type',
        'provider_event_id',
        'payload_hash',
        'payload',
        'status',
        'error_code',
        'occurred_at',
        'processed_at',
    ];

    protected $hidden = ['payload'];

    protected $casts = [
        'payload' => 'encrypted:array',
        'occurred_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
