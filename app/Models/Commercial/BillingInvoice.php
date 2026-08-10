<?php

namespace App\Models\Commercial;

use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingInvoice extends Model
{
    protected $fillable = [
        'company_id', 'subscription_id', 'payment_provider', 'provider_invoice_id',
        'status', 'currency', 'amount_due', 'amount_paid', 'period_start', 'period_end',
        'due_at', 'paid_at', 'hosted_invoice_url',
    ];

    protected $casts = [
        'amount_due' => 'decimal:2', 'amount_paid' => 'decimal:2',
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
}
