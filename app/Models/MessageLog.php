<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Client\Lead;     // 🔥 ADDED
use App\Models\User;            // 🔥 ADDED

class MessageLog extends Model
{
    protected $fillable = [
        'company_id','lead_id','conversation_id','user_id',
        'direction','channel','source',
        'to_number','from_number',
        'template','template_id','body',
        'provider_message_id','provider_status',
        'meta','ai_analysis','ai_confidence','ai_intent',
        'ai_propensity_score','ai_propensity_reason',
        'read_at','is_ai'
    ];

    protected $casts = [
        'meta'                => 'array',
        'ai_analysis'         => 'array',
        'ai_propensity_score' => 'integer',
        'ai_confidence'       => 'decimal:2',
        'read_at'             => 'datetime',
        'is_ai'               => 'boolean',
    ];

    /* ---------- Helpers ---------- */

    public static function in(array $data): self
    {
        $data['direction'] = 'in';
        $data['source']    = 'human';

        return static::create($data);
    }

    public static function out(array $data): self
    {
        $data['direction'] = 'out';
        $data['source']    = $data['source'] ?? 'template';
        $data['is_ai']     = $data['source'] === 'ai';

        return static::create($data);
    }

    /**
     * ✅ SLICE 1.5 — Conversion guard
     */
    public function hasConverted(): bool
    {
        return (bool) ($this->meta['converted'] ?? false);
    }

    public function markConverted(): void
    {
        $meta = is_array($this->meta) ? $this->meta : [];
        $meta['converted'] = true;

        $this->update([
            'meta' => $meta,
        ]);
    }

    protected static function booted()
    {
        static::created(function (self $m) {
            if (!$m->conversation_id) return;

            $conv = $m->conversation;
            if (!$conv) return;

            $conv->update([
                'last_message_at'      => now(),
                'latest_message_at'    => now(),
                'last_message_preview' => mb_strimwidth($m->body, 0, 140, '…'),
                'unread_count'         => $m->direction === 'in'
                    ? ($conv->unread_count + 1)
                    : $conv->unread_count,
            ]);
        });
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    // 🔥 ADDED → Needed for Manager View (link messages to lead)
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    // 🔥 ADDED → Who sent message (future UI / audit)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 🔥 ADDED → UI helpers
    public function isInbound(): bool
    {
        return $this->direction === 'in';
    }

    public function isOutbound(): bool
    {
        return $this->direction === 'out';
    }
}