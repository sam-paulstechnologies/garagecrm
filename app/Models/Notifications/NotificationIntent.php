<?php

namespace App\Models\Notifications;

use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationIntent extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'type', 'channel', 'state', 'title', 'body',
        'action_url', 'payload', 'idempotency_key', 'available_at',
        'dispatched_at', 'delivered_at', 'failure_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'available_at' => 'datetime',
        'dispatched_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
