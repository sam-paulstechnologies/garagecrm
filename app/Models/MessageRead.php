<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageRead extends Model
{
    protected $fillable = ['message_log_id','user_id','read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function message()
    {
        return $this->belongsTo(MessageLog::class, 'message_log_id');
    }
}
