<?php

namespace App\Messaging\Models;

use Illuminate\Database\Eloquent\Model;

class MessagingAuditLog extends Model
{
    protected $fillable = [
        'company_id', 'messaging_connection_id', 'user_id', 'product_key', 'operation',
        'result', 'provider_error_code', 'attempt_number', 'idempotency_key', 'context', 'occurred_at',
    ];

    protected $casts = ['context' => 'array', 'occurred_at' => 'datetime'];
}
