<?php

namespace App\Models\PlatformMarketing;

use Illuminate\Database\Eloquent\Model;

class PlatformMarketingAiRun extends Model
{
    protected $table = 'platform_marketing_ai_runs';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];
}
