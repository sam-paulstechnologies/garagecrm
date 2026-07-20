<?php

namespace App\Models\PlatformMarketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformMarketingProspect extends Model
{
    public const STATUSES = [
        'new',
        'ready_to_contact',
        'contacted',
        'delivered',
        'replied',
        'engaged',
        'qualified',
        'demo_requested',
        'demo_booked',
        'nurture',
        'won',
        'lost',
        'opted_out',
        'blocked',
        'invalid',
    ];

    protected $table = 'platform_marketing_prospects';

    protected $guarded = [];

    protected $casts = [
        'custom_metadata' => 'array',
        'consent_date' => 'datetime',
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'demo_requested_at' => 'datetime',
        'demo_booked_at' => 'datetime',
    ];

    public function conversations(): HasMany
    {
        return $this->hasMany(PlatformMarketingConversation::class, 'prospect_id');
    }

    public function campaignRecipients(): HasMany
    {
        return $this->hasMany(PlatformMarketingCampaignRecipient::class, 'prospect_id');
    }

    public function isSuppressed(): bool
    {
        return in_array($this->status, ['opted_out', 'blocked', 'invalid'], true)
            || $this->consent_status !== 'opted_in';
    }
}
