<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;

class JobDocument extends Model
{
    protected $table = 'job_documents';

    protected $fillable = [
        'client_id','job_id','type','source','sender_phone','sender_email','provider_message_id',
        'hash','original_name','mime','size','path','url','status','received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    public function job()    { return $this->belongsTo(Job::class); }
}
