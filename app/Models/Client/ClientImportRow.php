<?php

namespace App\Models\Client;

use App\Models\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientImportRow extends Model
{
    protected $fillable = [
        'batch_id',
        'company_id',
        'row_number',
        'raw_payload',
        'normalized_payload',
        'client_match_id',
        'vehicle_match_id',
        'duplicate_status',
        'validation_status',
        'errors',
        'warnings',
        'suggested_segment_code',
        'suggested_segment_label',
        'suggested_next_action_date',
        'suggested_message',
        'review_status',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'normalized_payload' => 'array',
        'errors' => 'array',
        'warnings' => 'array',
        'suggested_next_action_date' => 'date',
        'row_number' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ClientImportBatch::class, 'batch_id');
    }

    public function clientMatch(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_match_id');
    }

    public function vehicleMatch(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_match_id');
    }
}
