<?php

namespace App\Models\Commercial;

use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntitlementAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'company_id', 'subscription_id', 'actor_id', 'event', 'capability',
        'decision', 'source', 'context', 'created_at',
    ];

    protected $casts = [
        'decision' => 'boolean', 'context' => 'array', 'created_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
