<?php

namespace App\Models\Commercial;

use App\Models\MessageLog;
use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysisRun extends Model
{
    protected $fillable = [
        'company_id', 'message_log_id', 'ai_customer_usage_id', 'capability', 'status',
        'provider', 'model', 'input_tokens', 'output_tokens', 'total_tokens',
        'estimated_cost_micros', 'skipped_reason', 'error_code', 'duration_ms', 'metadata',
    ];

    protected $casts = [
        'input_tokens' => 'integer', 'output_tokens' => 'integer', 'total_tokens' => 'integer',
        'estimated_cost_micros' => 'integer', 'duration_ms' => 'integer', 'metadata' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(MessageLog::class, 'message_log_id');
    }

    public function customerUsage(): BelongsTo
    {
        return $this->belongsTo(AiCustomerUsage::class, 'ai_customer_usage_id');
    }
}
