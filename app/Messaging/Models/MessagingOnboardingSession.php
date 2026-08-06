<?php

namespace App\Messaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessagingOnboardingSession extends Model
{
    protected $fillable = [
        'public_id', 'company_id', 'user_id', 'product_key', 'provider', 'connection_mode',
        'state_hash', 'nonce_hash', 'status', 'session_event', 'messaging_connection_id',
        'expires_at', 'started_at', 'completed_at', 'last_attempted_at', 'attempt_count',
        'failure_code', 'failure_message', 'metadata',
    ];

    protected $hidden = ['state_hash', 'nonce_hash'];

    protected $casts = [
        'expires_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_attempted_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(MessagingConnection::class, 'messaging_connection_id');
    }
}
