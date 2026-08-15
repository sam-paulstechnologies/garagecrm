<?php

namespace Tests\Feature\Security;

use App\Billing\Exceptions\InvalidBillingWebhook;
use App\Billing\Gateways\FakeBillingGateway;
use Tests\TestCase;

/**
 * Phase 35 — the fake billing gateway must use a dedicated webhook secret and
 * must never fall back to APP_KEY. When the dedicated secret is unset it must
 * fail closed (verification rejects), never verify.
 */
class FakeBillingWebhookSecretTest extends TestCase
{
    public function test_config_has_no_app_key_fallback_for_the_fake_webhook_secret(): void
    {
        // A distinctive APP_KEY that must never be used as the signing secret.
        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('k', 32)),
            'billing.fake.webhook_secret' => null,
        ]);

        // The config value itself must be empty, not silently equal to APP_KEY.
        $this->assertEmpty(config('billing.fake.webhook_secret'));
        $this->assertNotSame(config('app.key'), (string) config('billing.fake.webhook_secret'));

        // A signature forged with APP_KEY must NOT verify (no fallback coupling).
        $payload = json_encode(['id' => 'evt_fake_no_fallback', 'type' => 'ping'], JSON_THROW_ON_ERROR);
        $appKeySignature = hash_hmac('sha256', $payload, (string) config('app.key'));

        $this->expectException(InvalidBillingWebhook::class);
        app(FakeBillingGateway::class)->verifyWebhook($payload, $appKeySignature);
    }

    public function test_empty_secret_fails_closed_for_any_signature(): void
    {
        config(['billing.fake.webhook_secret' => null]);

        $payload = json_encode(['id' => 'evt_fake_empty_secret', 'type' => 'ping'], JSON_THROW_ON_ERROR);

        $this->expectException(InvalidBillingWebhook::class);
        // Even a well-formed-looking signature must be rejected when no secret exists.
        app(FakeBillingGateway::class)->verifyWebhook($payload, hash_hmac('sha256', $payload, 'anything'));
    }

    public function test_configured_dedicated_secret_verifies_a_correctly_signed_payload(): void
    {
        config(['billing.fake.webhook_secret' => 'dedicated-fake-webhook-secret-2026']);

        $gateway = app(FakeBillingGateway::class);
        [$payload, $signature] = $gateway->signedEvent(
            'checkout.session.completed',
            'checkout.session',
            'cs_fake_verify_ok',
            ['local_checkout_id' => 7],
        );

        // Must not throw.
        $gateway->verifyWebhook($payload, $signature);

        // A tampered signature under the same (configured) secret must still reject.
        $this->expectException(InvalidBillingWebhook::class);
        $gateway->verifyWebhook($payload, hash_hmac('sha256', $payload.'tamper', 'dedicated-fake-webhook-secret-2026'));
    }
}
