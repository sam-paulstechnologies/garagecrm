<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    protected $table = 'message_logs';

    protected $fillable = [
        'company_id',
        'lead_id',
        'direction',         // 'in' or 'out'
        'channel',           // 'whatsapp'
        'to_number',
        'from_number',
        'template',
        'body',
        'provider_message_id',
        'provider_status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
