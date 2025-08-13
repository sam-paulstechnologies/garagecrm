<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'currency',
        'whatsapp_limit',
        'user_limit',
        'features',
        'status',
    ];

    protected $casts = [
        'features' => 'array',
    ];

    // 🔗 Relationships
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
