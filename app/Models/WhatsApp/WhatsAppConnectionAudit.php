<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Model;

class WhatsAppConnectionAudit extends Model
{
    protected $table = 'whatsapp_connection_audits';

    protected $fillable = [
        'company_id',
        'user_id',
        'event',
        'status',
        'connection_mode',
        'waba_id',
        'phone_number_id',
        'context',
        'occurred_at',
    ];

    protected $casts = [
        'context' => 'array',
        'occurred_at' => 'datetime',
    ];
}
