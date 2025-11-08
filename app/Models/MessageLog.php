<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageLog extends Model
{
    protected $table = 'message_logs';

    protected $fillable = [
        'company_id',
        'lead_id',
        'conversation_id',

        'direction',            // 'in' | 'out'
        'channel',              // 'whatsapp' | 'in_app'
        'source',               // 'ai' | 'template' | 'human'

        'to_number',
        'from_number',

        'template',
        'template_id',

        'body',
        'provider_message_id',
        'provider_status',
        'manager_alerted_at',
        'escalation_reason',

        'meta',

        // AI fields
        'ai_analysis',
        'ai_confidence',
        'ai_intent',
        'ai_propensity_score',
        'ai_propensity_reason',

        'read_at',
    ];

    protected $casts = [
        'meta'                 => 'array',
        'ai_analysis'          => 'array',
        'ai_propensity_score'  => 'integer',
        'ai_confidence'        => 'decimal:2',
        'manager_alerted_at'   => 'datetime',
        'read_at'              => 'datetime',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            $m->channel   = $m->channel ?: 'whatsapp';
            $m->direction = strtolower($m->direction ?? 'in') === 'out' ? 'out' : 'in';

            // normalize source
            if (!in_array($m->source, ['ai','template','human'], true)) {
                $m->source = $m->direction === 'in' ? 'human' : null;
            }

            // strip twilio prefix if present
            foreach (['to_number', 'from_number'] as $k) {
                if (!empty($m->{$k})) {
                    $m->{$k} = preg_replace('/^whatsapp:/', '', $m->{$k});
                }
            }
        });
    }

    // helpers
    public static function in(array $attrs): self
    {
        $attrs['direction'] = 'in';
        if (empty($attrs['source'])) $attrs['source'] = 'human';
        return static::create($attrs);
    }

    public static function out(array $attrs): self
    {
        $attrs['direction'] = 'out';
        return static::create($attrs);
    }

    // relations
    public function lead()
    {
        return $this->belongsTo(\App\Models\Client\Lead::class);
    }

    public function conversation()
    {
        return $this->belongsTo(\App\Models\Conversation::class);
    }

    public function suggestions()
    {
        return $this->hasMany(\App\Models\AiSuggestion::class, 'message_log_id');
    }

    // scopes
    public function scopeForCompany($q, int $companyId) { return $q->where('company_id', $companyId); }
    public function scopeInbound($q)  { return $q->where('direction', 'in'); }
    public function scopeOutbound($q) { return $q->where('direction', 'out'); }
}
