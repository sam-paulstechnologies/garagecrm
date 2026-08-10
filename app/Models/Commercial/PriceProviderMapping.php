<?php

namespace App\Models\Commercial;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceProviderMapping extends Model
{
    protected $fillable = ['price_id', 'payment_provider', 'price_phase', 'provider_price_id', 'status'];

    public function price(): BelongsTo
    {
        return $this->belongsTo(Price::class);
    }
}
