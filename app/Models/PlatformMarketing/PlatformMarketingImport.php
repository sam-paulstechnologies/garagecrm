<?php

namespace App\Models\PlatformMarketing;

use Illuminate\Database\Eloquent\Model;

class PlatformMarketingImport extends Model
{
    protected $table = 'platform_marketing_imports';

    protected $guarded = [];

    protected $casts = [
        'mapping' => 'array',
        'summary' => 'array',
    ];
}
