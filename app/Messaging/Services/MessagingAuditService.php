<?php

namespace App\Messaging\Services;

use App\Messaging\Models\MessagingAuditLog;
use App\Messaging\Support\SafeContext;

class MessagingAuditService
{
    public function record(
        ?int $companyId,
        ?int $connectionId,
        ?int $userId,
        string $productKey,
        string $operation,
        string $result,
        array $context = [],
        ?string $providerErrorCode = null,
        ?int $attempt = null,
        ?string $idempotencyKey = null,
    ): MessagingAuditLog {
        return MessagingAuditLog::query()->create([
            'company_id' => $companyId,
            'messaging_connection_id' => $connectionId,
            'user_id' => $userId,
            'product_key' => $productKey,
            'operation' => $operation,
            'result' => $result,
            'provider_error_code' => $providerErrorCode,
            'attempt_number' => $attempt,
            'idempotency_key' => $idempotencyKey,
            'context' => SafeContext::redact($context),
            'occurred_at' => now(),
        ]);
    }
}
