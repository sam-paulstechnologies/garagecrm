<?php

namespace App\Models\Commercial;

use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingCheckoutSession extends Model
{
    protected $fillable = [
        'company_id', 'subscription_id', 'requested_price_id', 'payment_provider', 'operation',
        'idempotency_key', 'provider_customer_id', 'provider_checkout_id', 'checkout_url',
        'status', 'expires_at', 'completed_at',
    ];

    protected $casts = ['expires_at' => 'datetime', 'completed_at' => 'datetime'];

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
}
