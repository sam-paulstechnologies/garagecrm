<?php

namespace App\Models\Commercial;

use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingCheckoutSession extends Model
{
    public const ACTIVE_STATUSES = ['creating', 'open', 'pending_provider', 'payment_pending'];

    public const TERMINAL_STATUSES = ['completed', 'cancelled', 'expired', 'payment_failed', 'failed'];

    protected $fillable = [
        'company_id', 'subscription_id', 'requested_price_id', 'price_phase',
        'launch_offer_qualified', 'payment_provider', 'operation',
        'idempotency_key', 'provider_customer_id', 'provider_checkout_id',
        'provider_subscription_id', 'provider_price_id', 'checkout_url',
        'status', 'expires_at', 'completed_at',
    ];

    protected $casts = [
        'launch_offer_qualified' => 'boolean',
        'expires_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function requestedPrice(): BelongsTo
    {
        return $this->belongsTo(Price::class, 'requested_price_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true)
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function isResumable(): bool
    {
        return $this->isActive() && filled($this->checkout_url);
    }
}
