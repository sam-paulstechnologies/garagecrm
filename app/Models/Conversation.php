<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
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

    public function messages()
    {
        return $this->hasMany(MessageLog::class)->orderBy('id');
    }

    public function markAllRead()
    {
        MessageLog::where('conversation_id', $this->id)
            ->where('direction', 'in')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->update(['unread_count' => 0]);
    }
}
