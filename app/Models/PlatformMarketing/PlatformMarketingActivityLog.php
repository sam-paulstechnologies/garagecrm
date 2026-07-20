<?php

namespace App\Models\PlatformMarketing;

use Illuminate\Database\Eloquent\Model;

class PlatformMarketingActivityLog extends Model
{
    protected $table = 'platform_marketing_activity_logs';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
    ];
}
