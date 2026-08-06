<?php

namespace App\Messaging\Models;

use Illuminate\Database\Eloquent\Model;

class MessagingConsent extends Model
{
    protected $fillable = [
        'company_id', 'messaging_connection_id', 'messaging_onboarding_session_id',
        'product_key', 'consent_version', 'accepted_by', 'accepted_at',
        'enabled_capabilities', 'revoked_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'revoked_at' => 'datetime',
        'enabled_capabilities' => 'array',
    ];
}
