<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Client\Client;

class AudienceMembership extends Model
{
    protected $fillable = [
        'company_id',
        'audience_id',
        'client_id',
        'added_by',
        'reason_json',
    ];

    protected $casts = [
        'reason_json' => 'array',
    ];

    public function audience(): BelongsTo
    {
        return $this->belongsTo(Audience::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
