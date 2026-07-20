<?php

namespace App\Models\PlatformMarketing;

use Illuminate\Database\Eloquent\Model;

class PlatformMarketingOptOut extends Model
{
    protected $table = 'platform_marketing_opt_outs';

    protected $guarded = [];

    protected $casts = [
        'opted_out_at' => 'datetime',
    ];
}
