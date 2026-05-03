<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JourneyAction extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'journey_id',
        'enrollment_id',
        'actor_user_id',
        'action',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'created_at' => 'datetime',
    ];
}
