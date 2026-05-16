<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleLeadWebhookLog extends Model
{
    protected $table = 'google_lead_webhook_logs';

    protected $fillable = [
        'company_id',
        'lead_source_id',
        'lead_id',
        'external_id',
        'external_form_id',
        'google_key_hash',
        'status',
        'http_status',
        'matched_existing_by',
        'error_message',
        'payload',
        'received_at',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function markInvalidKey(?string $message = null): void
    {
        $this->update([
            'status' => 'invalid_key',
            'http_status' => 401,
            'error_message' => $message,
            'processed_at' => now(),
        ]);
    }

    public function markInvalidPayload(?string $message = null): void
    {
        $this->update([
            'status' => 'invalid_payload',
            'http_status' => 422,
            'error_message' => $message,
            'processed_at' => now(),
        ]);
    }

    public function markProcessed(
        int $leadId,
        ?int $companyId = null,
        ?int $leadSourceId = null
    ): void {
        $this->update([
            'company_id' => $companyId ?? $this->company_id,
            'lead_source_id' => $leadSourceId ?? $this->lead_source_id,
            'lead_id' => $leadId,
            'status' => 'processed',
            'http_status' => 200,
            'processed_at' => now(),
        ]);
    }

    public function markDuplicate(
        int $leadId,
        string $matchedBy,
        ?int $companyId = null,
        ?int $leadSourceId = null
    ): void {
        $this->update([
            'company_id' => $companyId ?? $this->company_id,
            'lead_source_id' => $leadSourceId ?? $this->lead_source_id,
            'lead_id' => $leadId,
            'status' => 'duplicate',
            'http_status' => 200,
            'matched_existing_by' => $matchedBy,
            'processed_at' => now(),
        ]);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => 'failed',
            'http_status' => 500,
            'error_message' => $message,
            'processed_at' => now(),
        ]);
    }
}