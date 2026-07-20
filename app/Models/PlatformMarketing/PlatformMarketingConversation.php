<?php

namespace App\Models\PlatformMarketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformMarketingConversation extends Model
{
    protected $table = 'platform_marketing_conversations';

    protected $guarded = [];

    protected $casts = [
        'ai_enabled' => 'boolean',
        'human_takeover' => 'boolean',
        'last_message_at' => 'datetime',
        'context' => 'array',
    ];

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(PlatformMarketingProspect::class, 'prospect_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PlatformMarketingConversationMessage::class, 'conversation_id');
    }
}
