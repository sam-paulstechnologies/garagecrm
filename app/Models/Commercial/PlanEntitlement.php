<?php

namespace App\Models\Commercial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PlanEntitlement extends Model
{
    protected $fillable = [
        'plan_version_id', 'capability', 'value_type', 'value', 'allowance', 'enabled', 'mode',
    ];

    protected $casts = ['value' => 'array', 'allowance' => 'integer', 'enabled' => 'boolean'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Versioned plan entitlements are immutable. Create a new plan version.'));
        static::deleting(fn () => throw new LogicException('Versioned plan entitlements cannot be deleted through the model.'));
    }

    public function planVersion(): BelongsTo
    {
        return $this->belongsTo(PlanVersion::class);
    }
}
