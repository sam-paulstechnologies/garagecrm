<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityAuditLog extends Model
{
    protected $fillable = [
        'actor_user_id', 'target_user_id', 'company_id', 'event',
        'context', 'ip_address', 'user_agent',
    ];

    protected $casts = ['context' => 'array'];
}
