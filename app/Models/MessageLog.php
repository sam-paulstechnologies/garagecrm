<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    protected $table = 'message_logs';

    protected $fillable = [
        'company_id',
        'lead_id',
        'direction',            // 'in' | 'out'
        'channel',              // 'whatsapp'
        'to_number',
        'from_number',
        'template',
        'body',
        'provider_message_id',
        'provider_status',
        'meta',

        // AI fields
        'ai_analysis',
        'ai_propensity_score',
        'ai_propensity_reason',
    ];

    protected $casts = [
        'meta'                 => 'array',
        'ai_analysis'          => 'array',
        'ai_propensity_score'  => 'integer',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            $m->channel   = $m->channel ?: 'whatsapp';
            $m->direction = strtolower($m->direction ?? 'in') === 'out' ? 'out' : 'in';

            // normalize numbers to E.164 without "whatsapp:" prefix
            foreach (['to_number', 'from_number'] as $k) {
                if (!empty($m->{$k})) {
                    $m->{$k} = preg_replace('/^whatsapp:/', '', $m->{$k});
                }
            }
        });
    }

    /* ---------- tiny logging helpers ---------- */

    public static function in(array $attrs): self
    {
        $attrs['direction'] = 'in';
        return static::create($attrs);
    }

    public static function out(array $attrs): self
    {
        $attrs['direction'] = 'out';
        return static::create($attrs);
    }

    /* ---------- relations ---------- */
    public function lead()
    {
        return $this->belongsTo(\App\Models\Client\Lead::class);
    }

    /* ---------- scopes ---------- */
    public function scopeForCompany($q, int $companyId) { return $q->where('company_id', $companyId); }
    public function scopeInbound($q)  { return $q->where('direction', 'in'); }
    public function scopeOutbound($q) { return $q->where('direction', 'out'); }
}