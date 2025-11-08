<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSuggestion extends Model
{
    protected $fillable = ['message_log_id','suggestion_text','confidence','chosen'];

    protected $casts = [
        'chosen'     => 'boolean',
        'confidence' => 'decimal:2'
    ];

    public function message()
    {
        return $this->belongsTo(MessageLog::class, 'message_log_id');
    }
}
