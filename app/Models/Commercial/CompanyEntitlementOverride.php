<?php

namespace App\Models\Commercial;

use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyEntitlementOverride extends Model
{
    protected $fillable = [
        'company_id', 'capability', 'value_type', 'value', 'allowance', 'enabled', 'mode',
        'reason', 'approved_by', 'expires_at', 'revoked_at',
    ];

    protected $casts = [
        'value' => 'array', 'allowance' => 'integer', 'enabled' => 'boolean',
        'expires_at' => 'datetime', 'revoked_at' => 'datetime',
    ];

    public function scopeEffective(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
