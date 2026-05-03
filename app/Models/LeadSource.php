<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LeadSource extends Model
{
    protected $table = 'lead_sources';

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'status',
        'config',
        'form_token',
        'last_received_at',
    ];

    protected $casts = [
        'config' => 'array',
        'last_received_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($source) {
            if ($source->type === 'website' && empty($source->form_token)) {
                $source->form_token = Str::random(32);
            }
        });
    }
}
