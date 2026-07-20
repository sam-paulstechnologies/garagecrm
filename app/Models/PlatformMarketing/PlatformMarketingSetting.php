<?php

namespace App\Models\PlatformMarketing;

use Illuminate\Database\Eloquent\Model;

class PlatformMarketingSetting extends Model
{
    protected $table = 'platform_marketing_settings';

    protected $guarded = [];

    protected $casts = [
        'value' => 'array',
        'is_secret' => 'boolean',
    ];
}
