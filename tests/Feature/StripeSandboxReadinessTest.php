<?php

namespace Tests\Feature;

use App\Billing\Exceptions\InvalidBillingWebhook;
use App\Billing\Gateways\StripeBillingGateway;
use App\Commercial\Plans;
use App\Commercial\SubscriptionManager;
use App\Models\Commercial\BillingCheckoutSession;
use App\Models\Commercial\EntitlementAuditLog;
use App\Models\Commercial\Price;
use App\Models\Commercial\PriceProviderMapping;
use App\Models\System\Company;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StripeSandboxReadinessTest extends TestCase
{
    use RefreshDatabase;

    private const API_VERSION = StripeBillingGateway::SUPPORTED_API_VERSION;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set([
            'billing.provider' => 'fake',
            'billing.mode' => 'test',
            'billing.stripe.secret_key' => 'sk_test_fixture_mapping_guard',
            'billing.stripe.api_base' => 'https://api.stripe.com',
            'billing.stripe.api_version' => self::API_VERSION,
            'billing.stripe.webhook_secret' => 'whsec_fixture_test',
        ]);
        $this->seed(CommercialFoundationSeeder::class);
    }

    public function test_billing_webhook_has_a_narrow_framework_csrf_exception(): void
    {
        $excluded = app(ValidateCsrfToken::class)->getExcludedPaths();

        $this->assertContains('webhooks/billing/*', $excluded);
        $this->assertNotContains('webhooks/*', $excluded);
    }

    public function test_signed_unknown_subscription_deletion_is_acknowledged_once_without_tenant_mutation(): void
    {
        $company = Company::query()->create(['name' => 'Unmatched Event Safety Garage', 'status' => 'active']);
        app(SubscriptionManager::class)->assignPlan($company, Plans::FREE);
        $event = json_decode($this->fixture('customer.subscription.deleted'), true, flags: JSON_THROW_ON_ERROR);
        $event['data']['object']['currency'] = 'usd';
        $payload = json_encode($event, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        $this->postStripeWebhook($payload)
            ->assertOk()
            ->assertJson(['status' => 'ignored_unmatched', 'duplicate' => false]);
        $this->postStripeWebhook($payload)
            ->assertOk()
            ->assertJson(['status' => 'ignored_unmatched', 'duplicate' => true]);

        $this->assertDatabaseCount('billing_provider_events', 1);
        $this->assertDatabaseHas('billing_provider_events', [
            'payment_provider' => 'stripe',
            'event_type' => 'customer.subscription.deleted',
            'status' => 'ignored_unmatched',
        ]);
        $this->assertSame(Plans::FREE, $company->fresh()->subscription->planVersion->plan->code);
        $this->assertSame('not_required', $company->fresh()->subscription->payment_status);
    }

    public function test_webhook_verification_accepts_any_valid_v1_signature_during_secret_rotation(): void
    {
        $payload = $this->fixture('customer.subscription.deleted');
        $timestamp = now()->timestamp;
        $valid = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_fixture_test');

        app(StripeBillingGateway::class)->verifyWebhook(
            $payload,
            't='.$timestamp.',v1='.$valid.',v1='.str_repeat('0', 64),
        );

        $this->addToAssertionCount(1);
    }

    public function test_invalid_webhook_returns_only_a_safe_rejection_code(): void
    {
        $payload = $this->fixture('customer.subscription.deleted');

        $this->call('POST', route('billing.webhook', ['provider' => 'stripe']), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't='.now()->timestamp.',v1='.str_repeat('0', 64),
        ], $payload)->assertStatus(400)->assertExactJson([
            'received' => false,
            'reason' => 'signature_mismatch',
        ]);

        $this->assertDatabaseCount('billing_provider_events', 0);
    }

    public function test_mapping_command_dry_run_then_confirm_is_audited_and_idempotent(): void
    {
        $arguments = [
            'plan' => Plans::SERVICE,
            'phase' => 'launch',
            'provider_price_id' => 'price_testServiceLaunch001',
        ];

        $this->artisan('billing:map-stripe-sandbox-price', $arguments + ['--dry-run' => true])
            ->expectsOutputToContain('Dry run passed')
            ->assertSuccessful();
        $this->assertDatabaseCount('price_provider_mappings', 0);
        $this->assertDatabaseCount('entitlement_audit_logs', 0);

        $this->artisan('billing:map-stripe-sandbox-price', $arguments + ['--confirm' => true])
            ->expectsOutputToContain('Mapping created')
            ->assertSuccessful();
        $this->artisan('billing:map-stripe-sandbox-price', $arguments + ['--confirm' => true])
            ->expectsOutputToContain('already exact; no change')
            ->assertSuccessful();

        $price = Price::query()->where('code', 'service:2026-launch-v1:aed-monthly')->sole();
        $this->assertDatabaseHas('price_provider_mappings', [
            'price_id' => $price->id,
            'payment_provider' => 'stripe',
            'price_phase' => 'launch',
            'provider_price_id' => 'price_testServiceLaunch001',
            'status' => 'active',
        ]);
        $this->assertDatabaseCount('price_provider_mappings', 1);
        $this->assertSame(1, EntitlementAuditLog::query()
            ->where('event', 'billing.stripe_price_mapping_created')->count());
        $audit = EntitlementAuditLog::query()->where('event', 'billing.stripe_price_mapping_created')->sole();
        $this->assertSame(Plans::SERVICE, $audit->context['plan_code']);
        $this->assertSame('launch', $audit->context['price_phase']);
        $this->assertSame('AED', $audit->context['currency']);
        $this->assertSame('month', $audit->context['interval']);
        $this->assertSame('199.00', $audit->context['expected_amount']);
    }

    public function test_mapping_command_refuses_invalid_or_ambiguous_requests(): void
    {
        $this->artisan('billing:map-stripe-sandbox-price', [
            'plan' => Plans::FREE,
            'phase' => 'launch',
            'provider_price_id' => 'price_testFreeLaunch001',
            '--dry-run' => true,
        ])->assertFailed();
        $this->artisan('billing:map-stripe-sandbox-price', [
            'plan' => Plans::SERVICE,
            'phase' => 'annual',
            'provider_price_id' => 'price_testServiceAnnual001',
            '--dry-run' => true,
        ])->assertFailed();
        $this->artisan('billing:map-stripe-sandbox-price', [
            'plan' => Plans::SERVICE,
            'phase' => 'launch',
            'provider_price_id' => 'prod_not_a_price',
            '--dry-run' => true,
        ])->assertFailed();
        $this->artisan('billing:map-stripe-sandbox-price', [
            'plan' => Plans::SERVICE,
            'phase' => 'launch',
            'provider_price_id' => 'price_testNoModeSelected001',
        ])->assertFailed();

        $this->assertDatabaseCount('price_provider_mappings', 0);
    }

    public function test_mapping_command_supports_all_eight_canonical_paid_price_slots(): void
    {
        $slots = [
            [Plans::SERVICE, 'launch', 'price_testServiceLaunch001'],
            [Plans::SERVICE, 'standard', 'price_testServiceStandard001'],
            [Plans::GROWTH, 'launch', 'price_testGrowthLaunch001'],
            [Plans::GROWTH, 'standard', 'price_testGrowthStandard001'],
            [Plans::PERFORMANCE, 'launch', 'price_testPerformanceLaunch001'],
            [Plans::PERFORMANCE, 'standard', 'price_testPerformanceStandard001'],
            [Plans::AI_PRO, 'launch', 'price_testAiProLaunch001'],
            [Plans::AI_PRO, 'standard', 'price_testAiProStandard001'],
        ];

        foreach ($slots as [$plan, $phase, $providerPriceId]) {
            $this->artisan('billing:map-stripe-sandbox-price', [
                'plan' => $plan,
                'phase' => $phase,
                'provider_price_id' => $providerPriceId,
                '--confirm' => true,
            ])->assertSuccessful();
        }

        $this->assertSame(8, PriceProviderMapping::query()
            ->where('payment_provider', 'stripe')->where('status', 'active')->count());
        $this->assertSame(8, EntitlementAuditLog::query()
            ->where('event', 'billing.stripe_price_mapping_created')->count());
    }

    public function test_mapping_command_refuses_non_staging_runtime(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');

        $this->artisan('billing:map-stripe-sandbox-price', [
            'plan' => Plans::SERVICE,
            'phase' => 'launch',
            'provider_price_id' => 'price_testProductionRefused001',
            '--dry-run' => true,
        ])->expectsOutputToContain('restricted to staging/test environments')->assertFailed();
        $this->assertDatabaseCount('price_provider_mappings', 0);
    }

    public function test_mapping_command_refuses_slot_and_external_identifier_conflicts(): void
    {
        $service = Price::query()->where('code', 'service:2026-launch-v1:aed-monthly')->sole();
        PriceProviderMapping::query()->create([
            'price_id' => $service->id,
            'payment_provider' => 'stripe',
            'price_phase' => 'launch',
            'provider_price_id' => 'price_testExistingSlot001',
            'status' => 'active',
        ]);

        $this->artisan('billing:map-stripe-sandbox-price', [
            'plan' => Plans::SERVICE,
            'phase' => 'launch',
            'provider_price_id' => 'price_testConflictingSlot001',
            '--confirm' => true,
        ])->assertFailed();
        $this->artisan('billing:map-stripe-sandbox-price', [
            'plan' => Plans::GROWTH,
            'phase' => 'launch',
            'provider_price_id' => 'price_testExistingSlot001',
            '--confirm' => true,
        ])->assertFailed();

        $this->assertDatabaseCount('price_provider_mappings', 1);
        $this->assertSame('price_testExistingSlot001', PriceProviderMapping::query()->sole()->provider_price_id);
        $this->assertDatabaseCount('entitlement_audit_logs', 0);
    }

    public function test_mapping_command_safely_replaces_a_reviewed_typo_after_provider_validation(): void
    {
        $growth = Price::query()->where('code', 'growth:2026-launch-v1:aed-monthly')->sole();
        PriceProviderMapping::query()->create([
            'price_id' => $growth->id,
            'payment_provider' => 'stripe',
            'price_phase' => 'launch',
            'provider_price_id' => 'price_testGrowthTypo001',
            'status' => 'active',
        ]);
        Http::fake([
            'https://api.stripe.com/v1/prices/price_testGrowthValid001' => Http::response([
                'id' => 'price_testGrowthValid001',
                'active' => true,
                'livemode' => false,
                'currency' => 'aed',
                'unit_amount' => 99900,
                'recurring' => ['interval' => 'month', 'interval_count' => 1],
            ]),
        ]);
        $arguments = [
            'plan' => Plans::GROWTH,
            'phase' => 'launch',
            'provider_price_id' => 'price_testGrowthValid001',
            '--replace-current' => 'price_testGrowthTypo001',
        ];

        $this->artisan('billing:map-stripe-sandbox-price', $arguments + ['--dry-run' => true])
            ->expectsOutputToContain('Dry run replacement passed')
            ->assertSuccessful();
        $this->assertSame('price_testGrowthTypo001', PriceProviderMapping::query()->sole()->provider_price_id);

        $this->artisan('billing:map-stripe-sandbox-price', $arguments + ['--confirm' => true])
            ->expectsOutputToContain('Mapping corrected and audited')
            ->assertSuccessful();
        $this->artisan('billing:map-stripe-sandbox-price', $arguments + ['--confirm' => true])
            ->expectsOutputToContain('already corrected; no change')
            ->assertSuccessful();

        $this->assertSame('price_testGrowthValid001', PriceProviderMapping::query()->sole()->provider_price_id);
        $audit = EntitlementAuditLog::query()
            ->where('event', 'billing.stripe_price_mapping_corrected')
            ->sole();
        $this->assertSame('growth', $audit->context['plan_code']);
        $this->assertSame('price_testGrowthTypo001', $audit->context['previous_provider_price_id']);
        $this->assertSame('price_testGrowthValid001', $audit->context['provider_price_id']);
        Http::assertSentCount(3);
    }

    public function test_mapping_correction_refuses_provider_mismatch_and_subscription_references(): void
    {
        $growth = Price::query()->where('code', 'growth:2026-launch-v1:aed-monthly')->sole();
        $mapping = PriceProviderMapping::query()->create([
            'price_id' => $growth->id,
            'payment_provider' => 'stripe',
            'price_phase' => 'launch',
            'provider_price_id' => 'price_testGrowthTypo001',
            'status' => 'active',
        ]);
        Http::fake([
            'https://api.stripe.com/v1/prices/price_testGrowthWrong001' => Http::response([
                'id' => 'price_testGrowthWrong001',
                'active' => true,
                'livemode' => false,
                'currency' => 'aed',
                'unit_amount' => 99800,
                'recurring' => ['interval' => 'month', 'interval_count' => 1],
            ]),
            'https://api.stripe.com/v1/prices/price_testGrowthValid001' => Http::response([
                'id' => 'price_testGrowthValid001',
                'active' => true,
                'livemode' => false,
                'currency' => 'aed',
                'unit_amount' => 99900,
                'recurring' => ['interval' => 'month', 'interval_count' => 1],
            ]),
        ]);

        $this->artisan('billing:map-stripe-sandbox-price', [
            'plan' => Plans::GROWTH,
            'phase' => 'launch',
            'provider_price_id' => 'price_testGrowthWrong001',
            '--replace-current' => 'price_testGrowthTypo001',
            '--confirm' => true,
        ])->expectsOutputToContain('does not match the canonical')->assertFailed();

        $company = Company::query()->create(['name' => 'Mapping Reference Guard Garage', 'status' => 'active']);
        $subscription = app(SubscriptionManager::class)->assignPlan($company, Plans::FREE);
        $subscription->update([
            'payment_provider' => 'stripe',
            'provider_price_id' => $mapping->provider_price_id,
        ]);
        $this->artisan('billing:map-stripe-sandbox-price', [
            'plan' => Plans::GROWTH,
            'phase' => 'launch',
            'provider_price_id' => 'price_testGrowthValid001',
            '--replace-current' => 'price_testGrowthTypo001',
            '--confirm' => true,
        ])->expectsOutputToContain('existing Stripe subscription references')->assertFailed();

        $this->assertSame('price_testGrowthTypo001', $mapping->fresh()->provider_price_id);
        $this->assertDatabaseMissing('entitlement_audit_logs', [
            'event' => 'billing.stripe_price_mapping_corrected',
        ]);
    }

    public function test_mapping_command_validates_internal_aed_monthly_catalogue(): void
    {
        $price = Price::query()->where('code', 'service:2026-launch-v1:aed-monthly')->sole();
        DB::table('prices')->where('id', $price->id)->update(['currency' => 'USD']);

        $this->artisan('billing:map-stripe-sandbox-price', [
            'plan' => Plans::SERVICE,
            'phase' => 'launch',
            'provider_price_id' => 'price_testWrongCurrency001',
            '--dry-run' => true,
        ])->expectsOutputToContain('must use AED')->assertFailed();
        $this->assertDatabaseCount('price_provider_mappings', 0);
    }

    public function test_current_dahlia_fixtures_normalize_every_subscribed_event_shape(): void
    {
        $gateway = app(StripeBillingGateway::class);
        $expectations = [
            'checkout.session.completed' => ['checkout.session', 'cs_test_sayaraforce_completed'],
            'customer.subscription.created' => ['subscription', 'sub_test_sayaraforce'],
            'customer.subscription.updated' => ['subscription', 'sub_test_sayaraforce'],
            'customer.subscription.deleted' => ['subscription', 'sub_test_sayaraforce'],
            'invoice.paid' => ['invoice', 'in_test_sayaraforce_paid'],
            'invoice.payment_failed' => ['invoice', 'in_test_sayaraforce_failed'],
        ];

        foreach ($expectations as $type => [$objectType, $objectId]) {
            $event = $gateway->normalizeEvent($this->fixture($type));
            $this->assertSame($type, $event->type);
            $this->assertSame($objectType, $event->objectType);
            $this->assertSame($objectId, $event->objectId);
            $this->assertSame('price_testServiceLaunch001', $event->data['provider_price_id']);
            $this->assertTrue($event->data['test_mode']);
        }

        $subscription = $gateway->normalizeEvent($this->fixture('customer.subscription.updated'));
        $this->assertSame(1786464000, $subscription->data['period_start']);
        $this->assertSame(1789142400, $subscription->data['period_end']);
        $invoice = $gateway->normalizeEvent($this->fixture('invoice.paid'));
        $this->assertSame('sub_test_sayaraforce', $invoice->data['provider_subscription_id']);
        $this->assertSame(19900, $invoice->data['amount_paid_minor']);
    }

    public function test_incompatible_version_and_incomplete_supported_payload_fail_clearly(): void
    {
        $gateway = app(StripeBillingGateway::class);
        $wrongVersion = json_decode($this->fixture('invoice.paid'), true, flags: JSON_THROW_ON_ERROR);
        $wrongVersion['api_version'] = '2025-12-15.clover';

        try {
            $gateway->normalizeEvent(json_encode($wrongVersion, JSON_THROW_ON_ERROR));
            $this->fail('An incompatible webhook version was accepted.');
        } catch (InvalidBillingWebhook $exception) {
            $this->assertStringContainsString('expected 2026-07-29.dahlia', $exception->getMessage());
        }

        $incomplete = json_decode($this->fixture('invoice.paid'), true, flags: JSON_THROW_ON_ERROR);
        unset($incomplete['data']['object']['lines']['data'][0]['pricing']);
        unset($incomplete['data']['object']['parent']['subscription_details']['metadata']['provider_price_id']);
        $this->expectException(InvalidBillingWebhook::class);
        $this->expectExceptionMessage('missing required provider_price_id');
        $gateway->normalizeEvent(json_encode($incomplete, JSON_THROW_ON_ERROR));
    }

    public function test_subscription_update_invoice_uses_positive_target_price_not_stale_credit_metadata(): void
    {
        $payload = json_decode($this->fixture('invoice.paid'), true, flags: JSON_THROW_ON_ERROR);
        $payload['data']['object']['billing_reason'] = 'subscription_update';
        $payload['data']['object']['amount_due'] = 75925;
        $payload['data']['object']['amount_paid'] = 75925;
        $payload['data']['object']['parent']['subscription_details']['metadata']['provider_price_id'] = 'price_testServiceLaunch001';
        $payload['data']['object']['lines']['data'] = [
            [
                'amount' => -18887,
                'currency' => 'aed',
                'price' => ['id' => 'price_testServiceLaunch001', 'product' => 'prod_testService'],
                'period' => ['start' => 1786464000, 'end' => 1789142400],
                'proration' => true,
            ],
            [
                'amount' => 94812,
                'currency' => 'aed',
                'price' => ['id' => 'price_testGrowthLaunch001', 'product' => 'prod_testGrowth'],
                'period' => ['start' => 1786550400, 'end' => 1789142400],
                'proration' => true,
            ],
        ];

        $event = app(StripeBillingGateway::class)->normalizeEvent(json_encode($payload, JSON_THROW_ON_ERROR));

        $this->assertSame('price_testGrowthLaunch001', $event->data['provider_price_id']);
        $this->assertSame('subscription_update', $event->data['billing_reason']);
        $this->assertCount(2, $event->data['invoice_lines']);
        $this->assertSame(-18887, $event->data['invoice_lines'][0]['amount_minor']);
        $this->assertSame(94812, $event->data['invoice_lines'][1]['amount_minor']);
    }

    public function test_stripe_api_requests_pin_the_reviewed_version_header(): void
    {
        config()->set([
            'billing.stripe.secret_key' => 'sk_test_version_fixture',
            'billing.stripe.api_base' => 'https://api.stripe.test',
        ]);
        Http::fake(['https://api.stripe.test/v1/customers' => Http::response(['id' => 'cus_test_version'])]);

        app(StripeBillingGateway::class)->createCustomer(
            Company::query()->create(['name' => 'Version Header Garage', 'status' => 'active']),
            'version-header-idempotency',
        );

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Stripe-Version', self::API_VERSION)
        );
    }

    public function test_expired_checkout_becomes_terminal_and_cannot_activate_entitlements(): void
    {
        $company = Company::query()->create(['name' => 'Expired Checkout Garage', 'status' => 'active']);
        $subscription = app(SubscriptionManager::class)->assignPlan($company, Plans::FREE);
        $service = Price::query()->where('code', 'service:2026-launch-v1:aed-monthly')->sole();
        PriceProviderMapping::query()->create([
            'price_id' => $service->id,
            'payment_provider' => 'stripe',
            'price_phase' => 'launch',
            'provider_price_id' => 'price_testServiceLaunch001',
            'status' => 'active',
        ]);
        $checkout = BillingCheckoutSession::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'requested_price_id' => $service->id,
            'payment_provider' => 'stripe',
            'idempotency_key' => 'expired-checkout-idempotency-001',
            'provider_customer_id' => 'cus_test_sayaraforce',
            'provider_checkout_id' => 'cs_test_sayaraforce_expired',
            'status' => 'open',
        ]);

        $expired = $this->fixtureWithCheckout('checkout.session.expired', $checkout->id);
        $this->postStripeWebhook($expired)->assertOk()->assertJson(['status' => 'processed']);
        $this->assertSame('expired', $checkout->fresh()->status);
        $this->assertSame(Plans::FREE, $company->fresh()->subscription->planVersion->plan->code);
        $this->assertSame('not_required', $company->fresh()->subscription->payment_status);
        $this->assertDatabaseHas('entitlement_audit_logs', [
            'subscription_id' => $subscription->id,
            'event' => 'billing.checkout_expired',
        ]);

        $paid = $this->fixtureWithCheckout('invoice.paid', $checkout->id);
        $this->postStripeWebhook($paid)->assertStatus(503);
        $this->assertSame('expired', $checkout->fresh()->status);
        $this->assertSame(Plans::FREE, $company->fresh()->subscription->planVersion->plan->code);
        $this->assertDatabaseCount('billing_invoices', 0);
    }

    private function fixture(string $type): string
    {
        return file_get_contents(base_path('tests/Fixtures/Stripe/'.self::API_VERSION.'/'.$type.'.json')) ?: '';
    }

    private function fixtureWithCheckout(string $type, int $checkoutId): string
    {
        $payload = json_decode($this->fixture($type), true, flags: JSON_THROW_ON_ERROR);
        if ($type === 'checkout.session.expired') {
            $payload['data']['object']['metadata']['local_checkout_id'] = (string) $checkoutId;
        } else {
            $payload['data']['object']['parent']['subscription_details']['metadata']['local_checkout_id'] = (string) $checkoutId;
        }

        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function postStripeWebhook(string $payload): \Illuminate\Testing\TestResponse
    {
        $timestamp = now()->timestamp;
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_fixture_test');

        return $this->call('POST', route('billing.webhook', ['provider' => 'stripe']), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload);
    }
}
