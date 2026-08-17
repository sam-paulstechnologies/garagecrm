<?php

namespace App\Logging;

use Illuminate\Log\Logger as IlluminateLogger;
use Monolog\Logger;
use Monolog\LogRecord;

final class RedactSensitiveLogs
{
    private const SENSITIVE_KEY_PARTS = [
        // Secrets / credentials (original set)
        'authorization', 'password', 'passwd', 'secret', 'token', 'credential',
        'recovery', 'otp', 'totp', 'pin', 'cookie', 'signature', 'access_key',
        // Contact / phone PII
        'phone', 'phone_number', 'phone_e164', 'msisdn', 'mobile', 'whatsapp',
        'contact_phone', 'customer_identifier',
        // Email PII
        'email', 'e_mail',
        // Free-text / message payloads
        'message', 'message_body', 'body', 'text', 'content', 'prompt', 'response',
        // Name PII
        'display_name', 'full_name', 'first_name', 'last_name',
    ];

    /**
     * Maximum recursion depth when walking nested context/extra arrays.
     * Guards against deeply nested or cyclic-looking structures.
     */
    private const MAX_DEPTH = 16;

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

    private function redactArray(array $values, int $depth = 0): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return $values;
        }

        foreach ($values as $key => $value) {
            $name = strtolower((string) $key);

            if ($this->isSensitiveKey($name)) {
                $values[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $values[$key] = $this->redactArray($value, $depth + 1);
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
            // Stripe secret/publishable keys
            '/\b(?:sk|pk)_(?:live|test)_[A-Za-z0-9_-]+\b/i',
            // Stripe webhook signing secret
            '/\bwhsec_[A-Za-z0-9_-]+\b/i',
            // OpenAI-style API keys (sk-... incl. project keys sk-proj-...)
            '/\bsk-[A-Za-z0-9_-]{16,}\b/',
            // Meta / Facebook Graph access tokens
            '/\b(?:EAAB|EAAG|EAAJ|EAAK)[A-Za-z0-9_-]{12,}\b/',
            // Bearer tokens
            '/\bBearer\s+[A-Za-z0-9._~+\/-]+=*\b/i',
            // Secrets leaked in query strings
            '/([?&](?:code|token|secret|signature)=)[^&\s]+/i',
            // Email addresses appearing anywhere in free text
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/',
            // E.164 / long phone numbers: optional + then 8-15 digits.
            // Length + boundaries avoid clobbering short incrementing IDs.
            '/(?<![\w+])\+?\d{8,15}(?![\w])/',
        ];

        return preg_replace($patterns, '$1[REDACTED]', $value) ?? '[REDACTED]';
    }
}
