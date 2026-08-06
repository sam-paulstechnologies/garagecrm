<?php

namespace App\Messaging\Support;

final class SafeContext
{
    private const SENSITIVE_KEYS = [
        'access_token', 'token', 'app_secret', 'client_secret', 'authorization',
        'code', 'otp', 'pin', 'password', 'message', 'body', 'phone',
    ];

    public static function redact(array $context): array
    {
        $walk = function (array $values) use (&$walk): array {
            foreach ($values as $key => $value) {
                $name = strtolower((string) $key);
                if (collect(self::SENSITIVE_KEYS)->contains(fn (string $part): bool => str_contains($name, $part))) {
                    $values[$key] = '[REDACTED]';
                } elseif (is_array($value)) {
                    $values[$key] = $walk($value);
                } elseif (is_string($value)) {
                    $values[$key] = mb_substr($value, 0, 255);
                }
            }

            return $values;
        };

        return $walk($context);
    }
}
