<?php

namespace App\Models\Commercial;

use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    public const ENTITLED_STATES = ['active', 'trialing', 'grace', 'legacy_grandfathered'];

    protected $fillable = [
        'company_id', 'plan_version_id', 'price_id', 'status', 'started_at', 'current_period_start',
        'current_period_end', 'promotion_started_at', 'promotion_ends_at', 'trial_ends_at', 'cancel_at_period_end', 'cancelled_at',
        'grandfathered', 'grandfathered_snapshot', 'payment_provider', 'provider_customer_id',
        'provider_subscription_id', 'provider_price_id', 'payment_status',
    ];

    protected $casts = [
        'started_at' => 'datetime', 'current_period_start' => 'datetime',
        'current_period_end' => 'datetime', 'promotion_started_at' => 'datetime',
        'promotion_ends_at' => 'datetime', 'trial_ends_at' => 'datetime',
        'cancelled_at' => 'datetime', 'cancel_at_period_end' => 'boolean',
        'grandfathered' => 'boolean', 'grandfathered_snapshot' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function planVersion(): BelongsTo
    {
        return $this->belongsTo(PlanVersion::class);
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class);
    }

    public function isEntitledState(): bool
    {
        if (! in_array($this->status, self::ENTITLED_STATES, true)) {
            return false;
        }

        return $this->status !== 'trialing'
            || ($this->trial_ends_at !== null && $this->trial_ends_at->isFuture());
    }
}
