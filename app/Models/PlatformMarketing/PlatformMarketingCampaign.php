<?php

namespace App\Models\PlatformMarketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformMarketingCampaign extends Model
{
    protected $table = 'platform_marketing_campaigns';

    protected $guarded = [];

    protected $casts = [
        'template_variables' => 'array',
        'safety_snapshot' => 'array',
        'scheduled_at' => 'datetime',
        'approved_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'paused_at' => 'datetime',
        'stopped_at' => 'datetime',
    ];

    public function segment(): BelongsTo
    {
        return $this->belongsTo(PlatformMarketingSegment::class, 'segment_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(PlatformMarketingCampaignRecipient::class, 'campaign_id');
    }
}
