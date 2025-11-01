<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class WhatsAppMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'company_id',
        'campaign_id',
        'template_id',
        'to',                    // ✅ matches DB column
        'direction',             // ✅ matches DB column
        'status',                // ✅ matches DB column
        'external_id',           // ✅ matches DB column (Twilio SID or Meta message_id)
        'provider_message_id',   // ✅ matches DB column
        'error_message',         // ✅ matches DB column
        'payload',               // ✅ JSON payload
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    /* ----------------- Accessors ----------------- */
    // Optional: keep robust parsing for older data stored as TEXT
    protected function payload(): Attribute
    {
        return Attribute::make(
            get: fn($value) => is_array($value)
                ? $value
                : (json_decode($value ?? '[]', true) ?: []),
            set: fn($value) => is_array($value)
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
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    /* ----------------- Scopes ----------------- */
    public function scopeForCompany($q, $companyId)
    {
        return $q->where('company_id', $companyId);
    }
}
