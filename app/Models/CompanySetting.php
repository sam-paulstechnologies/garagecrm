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
    ];

    public $casts = [
        'value' => 'string', // we’ll json_decode in the store
    ];
}
