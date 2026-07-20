<?php

namespace App\Models\PlatformMarketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlatformMarketingSegment extends Model
{
    protected $table = 'platform_marketing_segments';

    protected $guarded = [];

    protected $casts = [
        'criteria' => 'array',
        'is_dynamic' => 'boolean',
    ];

    public function prospects(): BelongsToMany
    {
        return $this->belongsToMany(
            PlatformMarketingProspect::class,
            'platform_marketing_segment_members',
            'segment_id',
            'prospect_id'
        )->withTimestamps();
    }
}
