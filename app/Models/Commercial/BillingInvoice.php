<?php

namespace App\Models\Commercial;

use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingInvoice extends Model
{
    protected $fillable = [
        'company_id', 'subscription_id', 'billing_checkout_session_id', 'price_id',
        'payment_provider', 'test_mode', 'provider_invoice_id',
        'status', 'currency', 'amount_due', 'amount_paid', 'period_start', 'period_end',
        'due_at', 'paid_at', 'hosted_invoice_url',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2', 'amount_paid' => 'decimal:2',
        'test_mode' => 'boolean',
        'period_start' => 'datetime', 'period_end' => 'datetime',
        'due_at' => 'datetime', 'paid_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function checkoutSession(): BelongsTo
    {
        return $this->belongsTo(BillingCheckoutSession::class, 'billing_checkout_session_id');
    }

    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class);
    }
}
