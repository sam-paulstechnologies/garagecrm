<?php

namespace App\Models;

use App\Models\WhatsApp\WhatsAppTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'type',
        'channel',
        'message_template_id',
        'scheduled_at',
        'status',
        'description',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public const TYPE_BROADCAST = 'broadcast';
    public const TYPE_AUTOMATION = 'automation';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_RUNNING = 'running';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsAppTemplate::class, 'message_template_id');
    }

    public function audience(): HasMany
    {
        return $this->hasMany(CampaignAudience::class);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeWhatsapp($query)
    {
        return $query->where('channel', self::CHANNEL_WHATSAPP);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_SCHEDULED,
            self::STATUS_PAUSED,
        ], true);
    }

    public function isDispatchable(): bool
    {
        return in_array($this->status, [
            self::STATUS_SCHEDULED,
            self::STATUS_RUNNING,
        ], true);
    }
}