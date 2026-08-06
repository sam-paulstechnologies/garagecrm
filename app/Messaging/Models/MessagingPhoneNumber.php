<?php

namespace App\Messaging\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessagingPhoneNumber extends Model
{
    protected $fillable = [
        'messaging_connection_id', 'provider', 'phone_number_id', 'display_phone_number',
        'verified_name', 'display_name_status', 'quality_rating', 'registration_status',
        'coexistence_status', 'is_primary', 'last_health_check_at', 'metadata',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'last_health_check_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(MessagingConnection::class, 'messaging_connection_id');
    }
}
