<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $fillable = [
        'client_id',
        'content',
        'created_by',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
