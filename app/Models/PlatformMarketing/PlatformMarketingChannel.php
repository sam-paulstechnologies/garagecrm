<?php

namespace App\Models\PlatformMarketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class PlatformMarketingChannel extends Model
{
    protected $table = 'platform_marketing_channels';

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'last_inbound_at' => 'datetime',
        'last_outbound_at' => 'datetime',
        'template_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
        'verify_token',
    ];

    public function getDecryptedAccessTokenAttribute(): ?string
    {
        if (blank($this->access_token)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->access_token);
        } catch (\Throwable) {
            return null;
        }
    }

    public function getDecryptedVerifyTokenAttribute(): ?string
    {
        if (blank($this->verify_token)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->verify_token);
        } catch (\Throwable) {
            return null;
        }
    }

    public function getMaskedPhoneNumberIdAttribute(): ?string
    {
        return $this->mask($this->phone_number_id);
    }

    private function mask(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (strlen($value) <= 8) {
            return str_repeat('*', max(strlen($value) - 4, 0)).substr($value, -4);
        }

        return substr($value, 0, 4).'...'.substr($value, -4);
    }
}
