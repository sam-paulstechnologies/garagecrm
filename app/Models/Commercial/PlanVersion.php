<?php

namespace App\Models\Commercial;

use App\Models\System\Plan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class PlanVersion extends Model
{
    protected $fillable = ['plan_id', 'code', 'version', 'effective_from', 'effective_to', 'status'];

    protected $casts = ['effective_from' => 'datetime', 'effective_to' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Commercial plan versions are immutable. Create a new version.'));
        static::deleting(fn () => throw new LogicException('Commercial plan versions cannot be deleted through the model.'));
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(PlanEntitlement::class);
    }
}
