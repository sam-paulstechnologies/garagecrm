<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerAlert extends Model
{
    protected $fillable = [
        'company_id','message_log_id','reason','status','resolved_by','resolved_at'
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(MessageLog::class, 'message_log_id');
    }
}
