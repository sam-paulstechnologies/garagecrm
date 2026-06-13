<?php

namespace App\Models\Client;

use App\Models\Vehicle\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadUploadRow extends Model
{
    protected $fillable = [
        'batch_id',
        'company_id',
        'row_number',
        'raw_payload',
        'normalized_payload',
        'client_match_id',
        'lead_match_id',
        'vehicle_match_id',
        'duplicate_client_status',
        'duplicate_lead_status',
        'validation_status',
        'ack_readiness',
        'suggested_ack_event_key',
        'suggested_ack_template_key',
        'suggested_ack_message',
        'errors',
        'warnings',
        'review_status',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'normalized_payload' => 'array',
        'errors' => 'array',
        'warnings' => 'array',
        'row_number' => 'integer',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(LeadUploadBatch::class, 'batch_id');
    }

    public function clientMatch(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_match_id');
    }

    public function leadMatch(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_match_id');
    }

    public function vehicleMatch(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_match_id');
    }
}
