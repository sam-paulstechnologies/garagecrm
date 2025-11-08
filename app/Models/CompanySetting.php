<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $table = 'company_settings';

    protected $fillable = [
        'company_id',
        'key',
        'value',
        'group',
        'is_encrypted',
        'updated_by',
    ];

    protected $casts = [
        'value'       => 'string',
        'is_encrypted'=> 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
    ];
}
