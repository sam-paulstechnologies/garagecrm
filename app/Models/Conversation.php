<?php

namespace App\Models;

use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Contracts\TenantOwned;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

// M7: marked tenant-owned; manual/policy checks retained. Automatic global-scope
// backstop deferred (join-heavy inbox queries need qualified-column review).
class Conversation extends Model implements TenantOwned
{
    protected $fillable = [
        'company_id',
        'client_id',
        'lead_id',
        'customer_name',
        'customer_phone',
        'subject',
        'latest_message_at',
        'last_message_at',
        'last_message_preview',
        'unread_count',
        'is_whatsapp_linked',
    ];

    protected $casts = [
        'latest_message_at' => 'datetime',
        'last_message_at'   => 'datetime',
        'is_whatsapp_linked'=> 'boolean',
    ];

    public function messages(): HasMany
    {
        return $this->hasMany(MessageLog::class)->orderBy('id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function markAllRead(): void
    {
        MessageLog::where('conversation_id', $this->id)
            ->where('direction', 'in')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->update(['unread_count' => 0]);
    }
}
