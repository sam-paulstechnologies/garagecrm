<?php

namespace App\Messaging\Models;

use Illuminate\Database\Eloquent\Model;

class MessagingWebhookEvent extends Model
{
    protected $fillable = [
        'company_id', 'messaging_connection_id', 'product_key', 'provider', 'event_key',
        'field', 'event_type', 'provider_event_id', 'payload_hash', 'status', 'error_code',
        'metadata', 'occurred_at', 'processed_at',
    ];

    protected $casts = ['metadata' => 'array', 'occurred_at' => 'datetime', 'processed_at' => 'datetime'];
}
