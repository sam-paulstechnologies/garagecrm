<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    protected $fillable = [
        'company_id','name','channel','language','body','variables','is_active'
    ];
    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];
}
