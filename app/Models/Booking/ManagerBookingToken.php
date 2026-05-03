<?php

namespace App\Models\Booking;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ManagerBookingToken extends Model
{
    protected $table = 'manager_booking_tokens';

    protected $fillable = [
        'company_id',
        'opportunity_id',
        'token',
        'expires_at',
        'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Client\Opportunity::class);
    }

    public function isValid(): bool
    {
        return is_null($this->used_at)
            && $this->expires_at instanceof Carbon
            && $this->expires_at->isFuture();
    }
}
