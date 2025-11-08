<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'company_id','client_id','subject','latest_message_at','is_whatsapp_linked'
    ];

    protected $casts = [
        'latest_message_at' => 'datetime',
        'is_whatsapp_linked'=> 'boolean',
    ];

    public function messages()
    {
        return $this->hasMany(MessageLog::class, 'conversation_id')->orderBy('created_at');
    }

    public function participants()
    {
        return $this->hasMany(ConversationParticipant::class);
    }
}
