<?php

namespace App\Models;

use App\Models\WhatsApp\WhatsAppMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignAudience extends Model
{
    protected $fillable = [
        'campaign_id',
        'filters',
        'target_type',
        'target_id',
        'to',
        'status',
        'whatsapp_message_id',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function waMessage(): BelongsTo
    {
        return $this->belongsTo(WhatsAppMessage::class, 'whatsapp_message_id');
    }
}