<?php

namespace App\Models\Commercial;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class Price extends Model
{
    protected $fillable = [
        'plan_version_id', 'code', 'currency', 'interval', 'list_amount', 'promotional_amount',
        'promotion_effective_from', 'promotion_effective_to', 'promotion_duration_months',
        'renewal_behavior', 'status',
    ];

    protected $casts = [
        'list_amount' => 'decimal:2', 'promotional_amount' => 'decimal:2',
        'promotion_effective_from' => 'datetime', 'promotion_effective_to' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Historical price records are immutable. Create a new price record.'));
        static::deleting(fn () => throw new LogicException('Historical price records cannot be deleted through the model.'));
    }

    public function planVersion(): BelongsTo
    {
        return $this->belongsTo(PlanVersion::class);
    }

    public function isPromotionAvailableAt(CarbonInterface $at): bool
    {
        return $this->promotional_amount !== null
            && (! $this->promotion_effective_from || $at->greaterThanOrEqualTo($this->promotion_effective_from))
            && (! $this->promotion_effective_to || $at->lessThan($this->promotion_effective_to));
    }

    public function amountAt(CarbonInterface $at, ?CarbonInterface $promotionStartedAt = null): string
    {
        if (! $this->isPromotionAvailableAt($promotionStartedAt ?? $at)) {
            return (string) $this->list_amount;
        }

        if ($promotionStartedAt && $this->promotion_duration_months !== null
            && $at->greaterThanOrEqualTo($promotionStartedAt->copy()->addMonths($this->promotion_duration_months))) {
            return (string) $this->list_amount;
        }

        return (string) $this->promotional_amount;
    }
}
