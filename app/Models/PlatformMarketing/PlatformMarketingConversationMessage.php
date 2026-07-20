<?php

namespace App\Models\PlatformMarketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformMarketingConversationMessage extends Model
{
    protected $table = 'platform_marketing_conversation_messages';

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(PlatformMarketingConversation::class, 'conversation_id');
    }
}
