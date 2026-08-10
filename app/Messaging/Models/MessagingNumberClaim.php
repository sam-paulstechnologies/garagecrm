<?php

namespace App\Messaging\Models;

use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessagingNumberClaim extends Model
{
    public const PENDING_META = 'pending_meta';

    public const META_VERIFIED = 'meta_verified';

    public const READY = 'ready';

    protected $fillable = [
        'company_id', 'created_by', 'messaging_phone_number_id', 'phone_e164',
        'label', 'connection_mode', 'status', 'meta_verified_at',
    ];

    protected $casts = ['meta_verified_at' => 'datetime'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(MessagingPhoneNumber::class, 'messaging_phone_number_id');
    }

    public function onboardingSessions(): HasMany
    {
        return $this->hasMany(MessagingOnboardingSession::class, 'messaging_number_claim_id');
    }

    public function isUnverified(): bool
    {
        return $this->status === self::PENDING_META && $this->messaging_phone_number_id === null;
    }
}
