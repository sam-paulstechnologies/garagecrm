<?php

namespace App\Messaging\Models;

use App\Messaging\Enums\ConnectionStatus;
use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MessagingConnection extends Model
{
    protected $fillable = [
        'company_id', 'product_key', 'provider', 'status', 'connection_mode',
        'meta_business_id', 'waba_id', 'external_account_id', 'encrypted_access_token',
        'token_expires_at', 'connected_at', 'disconnected_at', 'last_verified_at',
        'failure_code', 'failure_message', 'metadata', 'created_by', 'updated_by',
    ];

    protected $hidden = ['encrypted_access_token'];

    protected $casts = [
        'status' => ConnectionStatus::class,
        'encrypted_access_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'connected_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(MessagingPhoneNumber::class);
    }

    public function primaryPhone(): HasOne
    {
        return $this->hasOne(MessagingPhoneNumber::class)->where('is_primary', true);
    }

    public function checks(): HasMany
    {
        return $this->hasMany(MessagingConnectionCheck::class);
    }

    public function audits(): HasMany
    {
        return $this->hasMany(MessagingAuditLog::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
