<?php

namespace App\Services\WhatsApp\History;

use RuntimeException;

class HistoryIdentity
{
    public function normalize(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?: '';
    }

    public function hash(string $value): string
    {
        $normalized = $this->normalize($value);
        $key = (string) config('messaging.history.hmac_key');
        if ($normalized === '' || $key === '') {
            throw new RuntimeException('WhatsApp history identity protection is unavailable.');
        }

        return hash_hmac('sha256', $normalized, $key);
    }

    public function connectionScope(int $companyId, ?int $connectionId, string $providerPhoneId): string
    {
        $key = (string) config('messaging.history.hmac_key');
        if ($key === '') {
            throw new RuntimeException('WhatsApp history identity protection is unavailable.');
        }

        return hash_hmac('sha256', implode('|', [$companyId, $connectionId ?: 'legacy', $providerPhoneId]), $key);
    }
}
