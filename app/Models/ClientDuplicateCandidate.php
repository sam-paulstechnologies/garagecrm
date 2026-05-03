<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Client\Client;

class ClientDuplicateCandidate extends Model
{
    protected $fillable = [
        'company_id',
        'client_a_id',
        'client_b_id',
        'match_score',
        'reasons_json',
        'status',
        'merged_into_id',
    ];

    protected $casts = [
        'reasons_json' => 'array',
    ];

    public function clientA(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_a_id');
    }

    public function clientB(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_b_id');
    }
}
