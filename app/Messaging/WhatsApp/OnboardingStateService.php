<?php

namespace App\Messaging\WhatsApp;

use App\Messaging\Exceptions\MessagingProvisioningException;
use App\Messaging\Models\MessagingOnboardingSession;
use Illuminate\Support\Str;

class OnboardingStateService
{
    public function issue(int $companyId, int $userId, string $productKey, string $mode): array
    {
        $publicId = (string) Str::uuid();
        $nonce = Str::random(48);
        $expiresAt = now()->addMinutes(max(5, (int) config('messaging.providers.meta_whatsapp.session_ttl_minutes', 15)));
        $payload = [
            'sid' => $publicId,
            'cid' => $companyId,
            'uid' => $userId,
            'product' => $productKey,
            'exp' => $expiresAt->getTimestamp(),
        ];
        $encoded = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $encoded, $this->signingKey(), true));
        $state = $encoded.'.'.$signature;

        $session = MessagingOnboardingSession::query()->create([
            'public_id' => $publicId,
            'company_id' => $companyId,
            'user_id' => $userId,
            'product_key' => $productKey,
            'provider' => 'meta_whatsapp',
            'connection_mode' => $mode,
            'state_hash' => hash('sha256', $state),
            'nonce_hash' => hash('sha256', $nonce),
            'status' => 'pending',
            'expires_at' => $expiresAt,
            'started_at' => now(),
            'metadata' => ['embedded_signup_version' => config('messaging.providers.meta_whatsapp.embedded_signup_version', 'v4')],
        ]);

        return ['session' => $session, 'state' => $state, 'nonce' => $nonce];
    }

    public function validate(string $state, string $nonce, int $companyId, int $userId, string $productKey): MessagingOnboardingSession
    {
        [$encoded, $signature] = array_pad(explode('.', $state, 2), 2, null);
        if (! $encoded || ! $signature) {
            throw new MessagingProvisioningException('invalid_state', 'The WhatsApp connection session is invalid. Restart the connection.');
        }

        $expected = $this->base64UrlEncode(hash_hmac('sha256', $encoded, $this->signingKey(), true));
        if (! hash_equals($expected, $signature)) {
            throw new MessagingProvisioningException('invalid_state', 'The WhatsApp connection session could not be verified.');
        }

        try {
            $claims = json_decode($this->base64UrlDecode($encoded), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $exception) {
            throw new MessagingProvisioningException('invalid_state', 'The WhatsApp connection session is invalid.', previous: $exception);
        }

        if ((int) ($claims['cid'] ?? 0) !== $companyId
            || (int) ($claims['uid'] ?? 0) !== $userId
            || (string) ($claims['product'] ?? '') !== $productKey
            || (int) ($claims['exp'] ?? 0) < now()->getTimestamp()) {
            throw new MessagingProvisioningException('state_mismatch', 'The WhatsApp connection session expired or belongs to another account.');
        }

        $session = MessagingOnboardingSession::query()
            ->where('public_id', (string) ($claims['sid'] ?? ''))
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->where('product_key', $productKey)
            ->first();

        if (! $session
            || ! hash_equals((string) $session->state_hash, hash('sha256', $state))
            || ! hash_equals((string) $session->nonce_hash, hash('sha256', $nonce))) {
            throw new MessagingProvisioningException('state_mismatch', 'The WhatsApp connection session could not be matched safely.');
        }

        if ($session->expires_at->isPast() && $session->status !== 'completed') {
            throw new MessagingProvisioningException('expired_session', 'The WhatsApp connection session expired. Start again.');
        }

        return $session;
    }

    private function signingKey(): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            $key = $decoded !== false ? $decoded : $key;
        }

        return $key;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return (string) base64_decode(strtr($value, '-_', '+/'), true);
    }
}
