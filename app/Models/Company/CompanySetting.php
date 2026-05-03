<?php

namespace App\Models\Company;

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
    ];

    protected $casts = [
        'company_id'   => 'integer',
        'is_encrypted' => 'boolean',
    ];
}
