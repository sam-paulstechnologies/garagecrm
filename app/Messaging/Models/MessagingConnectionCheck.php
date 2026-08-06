<?php

namespace App\Messaging\Models;

use Illuminate\Database\Eloquent\Model;

class MessagingConnectionCheck extends Model
{
    protected $fillable = [
        'messaging_connection_id', 'check_key', 'status', 'summary',
        'provider_error_code', 'metadata', 'checked_at',
    ];

    protected $casts = ['metadata' => 'array', 'checked_at' => 'datetime'];
}
