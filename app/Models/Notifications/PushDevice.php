<?php

namespace App\Models\Notifications;

use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushDevice extends Model
{
    protected $fillable = [
        'company_id', 'user_id', 'provider', 'platform', 'device_name',
        'token_hash', 'encrypted_token', 'status', 'last_seen_at', 'revoked_at',
    ];

    protected $hidden = ['encrypted_token', 'token_hash'];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
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
