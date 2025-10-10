<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        // keep these in sync with your current table
        'provider',          // e.g., twilio, meta
        'direction',         // out / in
        'to_number',         // +E164
        'from_number',       // +E164
        'template',          // template name (string)
        'payload',           // JSON string (we cast below)
        'status',            // queued/sent/delivered/read/failed
        'error_code',
        'error_message',
        'lead_id',
        'opportunity_id',
        'job_id',
        'company_id',
        'campaign_id',
        'template_id',
        'provider_message_id',
    ];

    protected $casts = [
        // if your DB column is JSON use 'array'; if TEXT keep the accessor/mutator below
        // 'payload' => 'array',
    ];

    // Accessors keep working even if column is TEXT
    protected function payload(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (is_array($value)) return $value;
                if (!$value) return [];
                try { return json_decode($value, true, 512, JSON_THROW_ON_ERROR); }
                catch (\Throwable) { return []; }
            },
            set: fn ($value) => is_array($value)
                ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : (string) $value
        );
    }

    /* ----------------- Relationships ----------------- */

    public function template()
    {
        return $this->belongsTo(WhatsAppTemplate::class, 'template_id');
    }

    public function campaign()
    {
        // You already have Models\WhatsApp\Campaign.php
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /* ----------------- Scopes ----------------- */

    public function scopeForCompany($q, $companyId)
    {
        return $q->where('company_id', $companyId);
    }
}
