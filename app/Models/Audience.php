<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Audience extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'entity_type',
        'description',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function rules(): HasMany
    {
        return $this->hasMany(AudienceRule::class);
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(AudienceMembership::class);
    }
}
