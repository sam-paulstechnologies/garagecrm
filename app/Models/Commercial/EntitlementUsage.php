<?php

namespace App\Models\Commercial;

use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntitlementUsage extends Model
{
    protected $fillable = ['company_id', 'capability', 'period_start', 'period_end', 'used', 'metadata'];

    protected $casts = [
        'period_start' => 'datetime', 'period_end' => 'datetime', 'used' => 'integer', 'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
