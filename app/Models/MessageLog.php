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
        'user_id',

        'direction',
        'channel',
        'source',

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
        'ai_analysis',
        'ai_confidence',
        'ai_intent',
        'ai_propensity_score',
        'ai_propensity_reason',

        'read_at',
        'is_ai',
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
        'is_ai'                => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            // Channel default
            $m->channel   = $m->channel ?: 'whatsapp';

            // Direction normalisation
            $m->direction = strtolower($m->direction ?? 'in') === 'out' ? 'out' : 'in';

            // Source default
            if (!in_array($m->source, ['ai', 'template', 'human'], true)) {
                $m->source = $m->direction === 'in' ? 'human' : null;
            }

            // Strip "whatsapp:" prefix from numbers
            foreach (['to_number', 'from_number'] as $k) {
                if (!empty($m->{$k})) {
                    $m->{$k} = preg_replace('/^whatsapp:/', '', $m->{$k});
                }
            }

            // If is_ai flag not set, infer from source
            if ($m->is_ai === null) {
                $m->is_ai = $m->source === 'ai';
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Mutators for from / to (virtual)
    |--------------------------------------------------------------------------
    | Lets the rest of the app use $log->from / $log->to while DB keeps
    | from_number / to_number.
    */

    public function getFromAttribute()
    {
        return $this->from_number;
    }

    public function setFromAttribute($value): void
    {
        $this->from_number = $value;
    }

    public function getToAttribute()
    {
        return $this->to_number;
    }

    public function setToAttribute($value): void
    {
        $this->to_number = $value;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function suggestions()
    {
        return $this->hasMany(\App\Models\AiSuggestion::class, 'message_log_id');
    }

    /*
    |--------------------------------------------------------------------------
    | API Shape for React Chat
    |--------------------------------------------------------------------------
    */

    public function toChatPayload(): array
    {
        return [
            'id'              => $this->id,
            'direction'       => $this->direction,             // 'in' | 'out'
            'body'            => $this->body,
            'channel'         => $this->channel,
            'is_ai'           => (bool) ($this->is_ai ?? ($this->source === 'ai')),
            'created_at'      => optional($this->created_at)->toIso8601String(),
            'provider_status' => $this->provider_status,
        ];
    }
}
