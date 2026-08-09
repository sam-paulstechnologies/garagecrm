<?php

namespace App\Models\Commercial;

use App\Messaging\Models\MessagingConnection;
use App\Models\MessageLog;
use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCustomerUsage extends Model
{
    protected $fillable = [
        'company_id', 'messaging_connection_id', 'connection_scope_hash', 'external_identity_hash',
        'period_start', 'period_end', 'first_message_log_id', 'last_message_log_id',
        'analysis_runs', 'input_tokens', 'output_tokens', 'estimated_cost_micros',
        'latest_model', 'last_status', 'last_skipped_reason',
    ];

    protected $casts = [
        'period_start' => 'datetime', 'period_end' => 'datetime', 'analysis_runs' => 'integer',
        'input_tokens' => 'integer', 'output_tokens' => 'integer', 'estimated_cost_micros' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function messagingConnection(): BelongsTo
    {
        return $this->belongsTo(MessagingConnection::class);
    }

    public function firstMessage(): BelongsTo
    {
        return $this->belongsTo(MessageLog::class, 'first_message_log_id');
    }
}
