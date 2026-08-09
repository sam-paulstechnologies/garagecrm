<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Commercial\PlanVersion;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'rank',
        'description',
        'price',
        'currency',
        'whatsapp_limit',
        'user_limit',
        'features',
        'status',
    ];

    protected $casts = [
        'features' => 'array',
        'status' => 'boolean',
        'rank' => 'integer',
    ];

    // 🔗 Relationships
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PlanVersion::class);
    }
}
