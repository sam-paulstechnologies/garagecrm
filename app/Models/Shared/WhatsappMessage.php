<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    protected $fillable = [
        'provider','direction','to_number','from_number','template','payload',
        'status','error_code','error_message','lead_id','opportunity_id','job_id'
    ];
}
