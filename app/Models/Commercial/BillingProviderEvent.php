<?php

namespace App\Models\Commercial;

use Illuminate\Database\Eloquent\Model;

class BillingProviderEvent extends Model
{
    protected $fillable = [
        'payment_provider', 'provider_event_id', 'event_type', 'event_created_at',
        'object_type', 'object_id', 'payload_hash', 'normalized_payload', 'status',
        'attempt_count', 'failure_reason', 'processed_at',
    ];

    protected $casts = [
        'event_created_at' => 'datetime', 'normalized_payload' => 'array',
        'attempt_count' => 'integer', 'processed_at' => 'datetime',
    ];
}
