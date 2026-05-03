<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutomationLog extends Model
{
    protected $fillable = [
        'company_id',
        'entity_type',
        'entity_id',
        'automation_type',
        'action',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
