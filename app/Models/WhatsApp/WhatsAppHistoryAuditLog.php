<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Model;

class WhatsAppHistoryAuditLog extends Model
{
    protected $table = 'whatsapp_history_audit_logs';

    public $timestamps = false;

    protected $fillable = ['company_id', 'batch_id', 'candidate_id', 'actor_id', 'event', 'context', 'created_at'];

    protected $casts = ['context' => 'array', 'created_at' => 'datetime'];
}
