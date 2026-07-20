<?php

namespace App\Models\PlatformMarketing;

use Illuminate\Database\Eloquent\Model;

class PlatformMarketingAppointment extends Model
{
    protected $table = 'platform_marketing_appointments';

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}
