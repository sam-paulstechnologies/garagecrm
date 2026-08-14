<?php

namespace App\Logging;

use Illuminate\Log\Logger as IlluminateLogger;
use Monolog\Logger;
use Monolog\LogRecord;

final class RedactSensitiveLogs
{
    private const SENSITIVE_KEY_PARTS = [
        'authorization', 'password', 'passwd', 'secret', 'token', 'credential',
        'recovery', 'otp', 'totp', 'pin', 'cookie', 'signature', 'access_key',
    ];

    public function __invoke(Logger|IlluminateLogger $logger): void
    {
        $monolog = $logger instanceof IlluminateLogger ? $logger->getLogger() : $logger;
        $monolog->pushProcessor(function (LogRecord $record): LogRecord {
            return $record->with(
                message: $this->redactString($record->message),
                context: $this->redactArray($record->context),
                extra: $this->redactArray($record->extra),
            );
        });
    }

    private function redactArray(array $values): array
    {
        foreach ($values as $key => $value) {
            $name = strtolower((string) $key);

            if ($this->isSensitiveKey($name)) {
                $values[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $values[$key] = $this->redactArray($value);
            } elseif (is_string($value)) {
                $values[$key] = $this->redactString($value);
            }
        }

        return $values;
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEY_PARTS as $part) {
            if (str_contains($key, $part)) {
                return true;
            }
        }

        return false;
    }

    private function redactString(string $value): string
    {
        $patterns = [
            '/\b(?:sk|pk)_(?:live|test)_[A-Za-z0-9_-]+\b/i',
            '/\bwhsec_[A-Za-z0-9_-]+\b/i',
            '/\b(?:EAAB|EAAG|EAAJ|EAAK)[A-Za-z0-9_-]{12,}\b/',
            '/\bBearer\s+[A-Za-z0-9._~+\/-]+=*\b/i',
            '/([?&](?:code|token|secret|signature)=)[^&\s]+/i',
        ];

        return preg_replace($patterns, '$1[REDACTED]', $value) ?? '[REDACTED]';
    }
}
