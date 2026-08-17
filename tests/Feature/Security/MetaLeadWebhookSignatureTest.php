<?php

namespace Tests\Feature\Security;

use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

/**
 * Phase 36 — the Meta Lead Ads webhook must require a valid
 * X-Hub-Signature-256 HMAC in ALL environments (the test suite runs in the
 * non-production "testing" environment). An unset app secret must fail closed,
 * and unsigned/invalid payloads must be rejected.
 *
 * The Meta WhatsApp webhook is intentionally untouched by these changes.
 */
class MetaLeadWebhookSignatureTest extends TestCase
{
    private const SECRET = 'meta-leads-test-app-secret-2026';

    protected function setUp(): void
    {
        parent::setUp();

        // Deterministic test secret injected via config.
        config([
            'services.meta_leads.app_secret' => self::SECRET,
            'services.meta.app_secret' => self::SECRET,
        ]);
    }

    private function postWebhook(string $body, array $server = [])
    {
        return $this->call('POST', route('api.webhooks.meta.leads.handle'), [], [], [], array_merge([
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $server), $body);
    }

    private function signature(string $body, string $secret): string
    {
        return 'sha256='.hash_hmac('sha256', $body, $secret);
    }

    public function test_unsigned_payload_is_rejected_in_non_production(): void
    {
        $this->assertFalse(app()->environment('production'));

        $body = json_encode(['object' => 'page', 'entry' => []], JSON_UNESCAPED_SLASHES);

        $this->postWebhook($body)->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_invalid_signature_is_rejected_in_non_production(): void
    {
        $body = json_encode(['object' => 'page', 'entry' => []], JSON_UNESCAPED_SLASHES);

        $this->postWebhook($body, [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signature($body, 'wrong-secret'),
        ])->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_missing_app_secret_fails_closed_even_in_non_production(): void
    {
        config([
            'services.meta_leads.app_secret' => null,
            'services.meta.app_secret' => null,
        ]);

        $body = json_encode(['object' => 'page', 'entry' => []], JSON_UNESCAPED_SLASHES);

        // Even with a syntactically valid-looking signature, no configured secret
        // means the request cannot be verified and must be rejected.
        $this->postWebhook($body, [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signature($body, 'anything'),
        ])->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_valid_signature_is_accepted_in_non_production(): void
    {
        $body = json_encode(['object' => 'page', 'entry' => []], JSON_UNESCAPED_SLASHES);

        $this->postWebhook($body, [
            'HTTP_X_HUB_SIGNATURE_256' => $this->signature($body, self::SECRET),
        ])->assertNoContent();
    }
}
