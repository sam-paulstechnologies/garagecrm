<?php

namespace App\Security;

use App\Models\SecurityAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use InvalidArgumentException;

class SecurityAudit
{
    private const FORBIDDEN_KEYS = [
        'password', 'code', 'otp', 'secret', 'seed', 'recovery_code',
        'recovery_codes', 'qr', 'qr_code', 'qr_payload',
    ];

    public function record(
        string $event,
        ?User $actor,
        ?User $target = null,
        array $context = [],
        ?Request $request = null,
    ): SecurityAuditLog {
        $this->assertSafeContext($context);
        $request ??= request();

        return SecurityAuditLog::query()->create([
            'actor_user_id' => $actor?->id,
            'target_user_id' => $target?->id ?? $actor?->id,
            'company_id' => $target?->company_id ?? $actor?->company_id,
            'event' => $event,
            'context' => $context ?: null,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 500) ?: null,
        ]);
    }

    private function assertSafeContext(array $context): void
    {
        foreach ($context as $key => $value) {
            if (in_array(strtolower((string) $key), self::FORBIDDEN_KEYS, true)) {
                throw new InvalidArgumentException('Sensitive authentication material cannot be audited.');
            }

            if (is_array($value)) {
                $this->assertSafeContext($value);
            }
        }
    }
}
