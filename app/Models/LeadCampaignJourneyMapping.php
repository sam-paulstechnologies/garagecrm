<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadCampaignJourneyMapping extends Model
{
    protected $fillable = [
        'company_id',
        'garage_id',
        'campaign_type',
        'journey_key',
        'journey_label',
        'journey_trigger_key',
        'is_active',
        'preview_only',
        'whatsapp_enabled',
        'whatsapp_template_name',
        'followup_template_name',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'garage_id' => 'integer',
        'is_active' => 'boolean',
        'preview_only' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
