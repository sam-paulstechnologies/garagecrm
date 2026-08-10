<?php

namespace Tests\Feature;

use App\Billing\BillingGatewayResolver;
use App\Billing\BillingWebhookProcessor;
use App\Billing\Exceptions\BillingConfigurationException;
use App\Billing\Gateways\FakeBillingGateway;
use App\Billing\Gateways\StripeBillingGateway;
use App\Commercial\Plans;
use App\Commercial\SubscriptionManager;
use App\Jobs\TransitionIntroductoryBillingPrice;
use App\Models\Commercial\BillingCheckoutSession;
use App\Models\Commercial\Price;
use App\Models\Commercial\PriceProviderMapping;
use App\Models\System\Company;
use App\Models\User;
use Database\Seeders\BillingFoundationSeeder;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BillingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set([
            'billing.provider' => 'fake',
            'billing.mode' => 'test',
            'billing.checkout_enabled' => true,
            'billing.fake.webhook_secret' => 'phase-3-fake-webhook-secret',
            'billing.failed_payment.grace_days' => 7,
        ]);
        $this->seed(CommercialFoundationSeeder::class);
        $this->seed(BillingFoundationSeeder::class);
    }

    public function test_checkout_redirect_cannot_activate_entitlements_and_verified_fake_event_activates_once(): void
    {
        [$company, $admin] = $this->tenant(Plans::FREE, 'Checkout Garage');
        $service = Price::query()->where('code', 'service:2026-launch-v1:aed-monthly')->firstOrFail();
        $key = 'checkout-idempotency-000000000001';

        $response = $this->actingAs($admin)->post(route('admin.billing.checkout'), [
            'price_code' => $service->code,
            'idempotency_key' => $key,
        ]);
        $checkout = BillingCheckoutSession::query()->sole();
        $response->assertRedirect(route('admin.billing.fake.show', $checkout));
        $this->assertSame(Plans::FREE, $company->fresh()->subscription->planVersion->plan->code);
        $this->actingAs($admin)->get(route('admin.billing.success', $checkout))->assertSessionHas('warning');
        $this->assertSame(Plans::FREE, $company->fresh()->subscription->planVersion->plan->code);

        $this->actingAs($admin)->post(route('admin.billing.fake.complete', $checkout))
            ->assertRedirect(route('admin.billing.success', ['checkout' => $checkout->id]));
        $subscription = $company->fresh()->subscription;
        $this->assertSame(Plans::SERVICE, $subscription->planVersion->plan->code);
        $this->assertSame('active', $subscription->status);
        $this->assertSame('fake', $subscription->payment_provider);
        $this->assertSame('paid', $subscription->payment_status);
        $this->assertDatabaseCount('billing_provider_events', 1);

        $this->actingAs($admin)->post(route('admin.billing.fake.complete', $checkout))->assertRedirect();
        $this->assertDatabaseCount('billing_provider_events', 1);
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_checkout_request_is_idempotent_and_cannot_be_reused_across_tenants(): void
    {
        [$companyA, $adminA] = $this->tenant(Plans::FREE, 'Checkout A');
        [, $adminB] = $this->tenant(Plans::FREE, 'Checkout B');
        $price = Price::query()->where('code', 'growth:2026-launch-v1:aed-monthly')->firstOrFail();
        $payload = ['price_code' => $price->code, 'idempotency_key' => 'checkout-shared-key-000000000001'];

        $this->actingAs($adminA)->post(route('admin.billing.checkout'), $payload)->assertRedirect();
        $this->actingAs($adminA)->post(route('admin.billing.checkout'), $payload)->assertRedirect();
        $this->assertDatabaseCount('billing_checkout_sessions', 1);
        $this->actingAs($adminB)->post(route('admin.billing.checkout'), $payload)->assertSessionHas('warning');
        $this->assertDatabaseCount('billing_checkout_sessions', 1);
        $this->assertSame(Plans::FREE, $companyA->fresh()->subscription->planVersion->plan->code);
    }

    public function test_invalid_signature_is_rejected_and_stripe_event_replay_is_idempotent(): void
    {
        $payload = json_encode([
            'id' => 'evt_stripe_replay_1', 'type' => 'customer.updated', 'created' => now()->timestamp,
            'data' => ['object' => ['id' => 'cus_test_only', 'object' => 'customer']],
        ], JSON_THROW_ON_ERROR);
        config()->set('billing.stripe.webhook_secret', 'whsec_test_phase3');

        $this->postJson(route('billing.webhook', ['provider' => 'stripe']), [], ['Stripe-Signature' => 'invalid'])
            ->assertStatus(400);
        $this->assertDatabaseCount('billing_provider_events', 0);

        $signature = $this->stripeSignature($payload, 'whsec_test_phase3');
        $this->call('POST', route('billing.webhook', ['provider' => 'stripe']), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload)->assertOk()->assertJson(['status' => 'ignored_unsupported', 'duplicate' => false]);
        $this->call('POST', route('billing.webhook', ['provider' => 'stripe']), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload)->assertOk()->assertJson(['duplicate' => true]);
        $this->assertDatabaseCount('billing_provider_events', 1);

        $tampered = str_replace('customer.updated', 'customer.created', $payload);
        $this->call('POST', route('billing.webhook', ['provider' => 'stripe']), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($tampered, 'whsec_test_phase3'),
        ], $tampered)->assertStatus(400);
        $this->assertDatabaseCount('billing_provider_events', 1);
    }

    public function test_failed_payment_enters_configurable_grace_then_suspends_only_after_command(): void
    {
        [$company, $admin] = $this->activatedServiceTenant();
        $subscription = $company->fresh()->subscription;
        $this->assertSame('active', $subscription->status);

        $this->fakeEvent('invoice.payment_failed', 'invoice', 'in_fake_failed_1', [
            'provider_subscription_id' => $subscription->provider_subscription_id,
            'provider_invoice_id' => 'in_fake_failed_1',
            'currency' => 'aed',
            'amount_due_minor' => 19900,
            'amount_paid_minor' => 0,
            'period_start' => now()->timestamp,
            'period_end' => now()->addMonth()->timestamp,
        ]);
        $subscription = $subscription->fresh();
        $this->assertSame('grace', $subscription->status);
        $this->assertSame('past_due', $subscription->payment_status);
        $this->assertTrue($subscription->grace_ends_at->isSameDay(now()->addDays(7)));
        $this->assertTrue($subscription->isEntitledState());

        $this->travel(8)->days();
        $this->artisan('billing:enforce-grace-periods', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame('grace', $subscription->fresh()->status);
        $this->artisan('billing:enforce-grace-periods')->assertSuccessful();
        $this->assertSame('suspended', $subscription->fresh()->status);
        $this->assertFalse($subscription->fresh()->isEntitledState());
    }

    public function test_out_of_order_provider_event_is_recorded_but_cannot_roll_state_back(): void
    {
        [$company] = $this->activatedServiceTenant();
        $subscription = $company->fresh()->subscription;
        $older = $subscription->provider_state_updated_at->copy()->subMinute()->timestamp;

        $result = $this->fakeEvent('customer.subscription.updated', 'subscription', (string) $subscription->provider_subscription_id, [
            'provider_subscription_id' => $subscription->provider_subscription_id,
            'subscription_status' => 'canceled',
        ], created: $older);
        $this->assertSame('ignored_out_of_order', $result['status']);
        $this->assertSame('active', $subscription->fresh()->status);
    }

    public function test_cancel_request_does_not_change_entitlements_before_provider_webhook(): void
    {
        [$company, $admin] = $this->activatedServiceTenant();
        $this->actingAs($admin)->post(route('admin.billing.cancel'))->assertSessionHas('success');
        $subscription = $company->fresh()->subscription;
        $this->assertSame('active', $subscription->status);
        $this->assertNotNull($subscription->cancellation_requested_at);

        $this->fakeEvent('customer.subscription.updated', 'subscription', (string) $subscription->provider_subscription_id, [
            'provider_subscription_id' => $subscription->provider_subscription_id,
            'subscription_status' => 'active',
            'cancel_at_period_end' => true,
        ], created: $subscription->provider_state_updated_at->copy()->addSecond()->timestamp);
        $this->assertSame('cancel_at_period_end', $subscription->fresh()->status);
        $this->assertTrue($subscription->fresh()->isEntitledState());
    }

    public function test_verified_plan_change_reuses_the_provider_subscription_and_never_creates_a_second_local_subscription(): void
    {
        [$company, $admin] = $this->activatedServiceTenant();
        $before = $company->fresh()->subscription;
        $growth = Price::query()->where('code', 'growth:2026-launch-v1:aed-monthly')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.billing.checkout'), [
            'price_code' => $growth->code,
            'idempotency_key' => 'plan-change-'.bin2hex(random_bytes(16)),
        ])->assertRedirect();
        $change = BillingCheckoutSession::query()->where('company_id', $company->id)->latest('id')->firstOrFail();
        $this->assertSame('plan_change', $change->operation);
        $this->assertSame(Plans::SERVICE, $company->fresh()->subscription->planVersion->plan->code);

        $this->actingAs($admin)->post(route('admin.billing.fake.complete', $change))->assertRedirect();
        $after = $company->fresh()->subscription;
        $this->assertSame(Plans::GROWTH, $after->planVersion->plan->code);
        $this->assertSame($before->provider_subscription_id, $after->provider_subscription_id);
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_billing_ui_is_tenant_admin_only_and_scoped(): void
    {
        [$company, $admin] = $this->tenant(Plans::FREE, 'Billing UI');
        $manager = User::query()->create([
            'company_id' => $company->id, 'name' => 'Manager', 'email' => 'billing-manager@example.test',
            'password' => 'Secure-Password-2026!', 'role' => 'manager', 'status' => true,
            'must_change_password' => false,
        ]);
        $this->actingAs($admin)->get(route('admin.billing.index'))->assertOk()->assertSee('Plan &amp; Billing', false);
        $this->actingAs($manager)->get(route('admin.billing.index'))->assertForbidden();
    }

    public function test_stripe_adapter_refuses_live_mode_and_live_credentials(): void
    {
        config()->set([
            'billing.mode' => 'live',
            'billing.stripe.secret_key' => 'forbidden-non-test-key',
        ]);
        Http::fake();
        $this->expectException(BillingConfigurationException::class);
        app(StripeBillingGateway::class)->createCustomer(Company::query()->create(['name' => 'No Live Charges', 'status' => 'active']), 'stripe-live-refusal');
    }

    public function test_stripe_test_adapter_implements_provider_neutral_customer_checkout_subscription_change_cancel_and_portal(): void
    {
        config()->set([
            'billing.mode' => 'test',
            'billing.stripe.secret_key' => 'sk_test_phase3_adapter',
            'billing.stripe.api_base' => 'https://api.stripe.test',
        ]);
        $price = Price::query()->where('code', 'service:2026-launch-v1:aed-monthly')->firstOrFail();
        $mapping = PriceProviderMapping::query()->create([
            'price_id' => $price->id, 'payment_provider' => 'stripe', 'price_phase' => 'launch',
            'provider_price_id' => 'price_test_service_launch', 'status' => 'active',
        ]);
        Http::fake(function (Request $request) {
            $path = parse_url($request->url(), PHP_URL_PATH);
            if ($path === '/v1/customers') {
                return Http::response(['id' => 'cus_test_phase3']);
            }
            if ($path === '/v1/checkout/sessions') {
                return Http::response(['id' => 'cs_test_phase3', 'url' => 'https://checkout.stripe.test/cs_test_phase3', 'expires_at' => now()->addHour()->timestamp]);
            }
            if ($path === '/v1/billing_portal/sessions') {
                return Http::response(['url' => 'https://billing.stripe.test/session']);
            }
            if ($path === '/v1/subscriptions') {
                return Http::response($this->stripeSubscriptionPayload());
            }
            if ($path === '/v1/subscriptions/sub_test_phase3') {
                return Http::response($this->stripeSubscriptionPayload());
            }

            return Http::response([], 404);
        });

        $company = Company::query()->create(['name' => 'Stripe Test Adapter', 'email' => 'stripe-adapter@example.test', 'status' => 'active']);
        $gateway = app(StripeBillingGateway::class);
        $customer = $gateway->createCustomer($company, 'customer-test-idempotency');
        $this->assertSame('cus_test_phase3', $customer->id);
        $checkout = $gateway->createCheckout($customer, $mapping, 'https://example.test/success', 'https://example.test/cancel', 'checkout-test-idempotency', ['local_checkout_id' => 42]);
        $this->assertSame('cs_test_phase3', $checkout->id);
        $this->assertSame('https://checkout.stripe.test/cs_test_phase3', $checkout->url);
        $this->assertSame('sub_test_phase3', $gateway->createSubscription($customer, $mapping, 'subscription-test-idempotency')->id);
        $this->assertSame('sub_test_phase3', $gateway->retrieveSubscription('sub_test_phase3')->id);
        $this->assertSame('price_test_service_launch', $gateway->changeSubscription('sub_test_phase3', $mapping, 'change-test-idempotency')->priceId);
        $this->assertTrue($gateway->cancelSubscription('sub_test_phase3')->cancelAtPeriodEnd);
        $this->assertSame('https://billing.stripe.test/session', $gateway->createBillingPortal($customer->id, 'https://example.test/billing'));
        Http::assertSentCount(8);
    }

    public function test_introductory_price_transition_is_deterministic_and_idempotent(): void
    {
        [$company] = $this->activatedServiceTenant();
        $subscription = $company->fresh()->subscription;
        $subscription->update(['introductory_cycles_completed' => 12]);

        $job = new TransitionIntroductoryBillingPrice($subscription->id);
        $job->handle(app(BillingGatewayResolver::class));
        $this->assertNotNull($subscription->fresh()->standard_price_transition_requested_at);
        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame(Plans::SERVICE, $subscription->fresh()->planVersion->plan->code);
        $this->assertDatabaseHas('entitlement_audit_logs', [
            'subscription_id' => $subscription->id,
            'event' => 'billing.standard_price_transition_requested',
        ]);

        $job->handle(app(BillingGatewayResolver::class));
        $this->assertSame(1, \App\Models\Commercial\EntitlementAuditLog::query()
            ->where('subscription_id', $subscription->id)
            ->where('event', 'billing.standard_price_transition_requested')->count());
    }

    public function test_verified_paid_invoice_increments_introductory_cycle_and_records_revenue(): void
    {
        [$company] = $this->activatedServiceTenant();
        $subscription = $company->fresh()->subscription;

        $result = $this->fakeEvent('invoice.paid', 'invoice', 'invoice_cycle_one', [
            'provider_subscription_id' => $subscription->provider_subscription_id,
            'provider_invoice_id' => 'invoice_cycle_one',
            'currency' => 'AED',
            'amount_due_minor' => 19900,
            'amount_paid_minor' => 19900,
            'period_start' => now()->timestamp,
            'period_end' => now()->addMonth()->timestamp,
            'paid_at' => now()->timestamp,
        ]);

        $this->assertSame('processed', $result['status']);
        $this->assertSame(1, $subscription->fresh()->introductory_cycles_completed);
        $this->assertDatabaseHas('billing_invoices', [
            'company_id' => $company->id,
            'provider_invoice_id' => 'invoice_cycle_one',
            'status' => 'paid',
            'currency' => 'AED',
            'amount_paid' => '199.00',
        ]);
    }

    /** @return array{Company, User} */
    private function tenant(string $plan, string $name): array
    {
        $company = Company::query()->create(['name' => $name, 'email' => str($name)->slug().'@example.test', 'status' => 'active']);
        app(SubscriptionManager::class)->assignPlan($company, $plan);
        $user = User::query()->create([
            'company_id' => $company->id, 'name' => $name.' Admin',
            'email' => str($name)->slug().'-admin@example.test', 'password' => 'Secure-Password-2026!',
            'role' => 'admin', 'status' => true, 'must_change_password' => false,
        ]);

        return [$company->fresh(), $user];
    }

    /** @return array{Company, User} */
    private function activatedServiceTenant(): array
    {
        [$company, $admin] = $this->tenant(Plans::FREE, 'Activated '.bin2hex(random_bytes(3)));
        $price = Price::query()->where('code', 'service:2026-launch-v1:aed-monthly')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.billing.checkout'), [
            'price_code' => $price->code,
            'idempotency_key' => 'activate-'.bin2hex(random_bytes(16)),
        ])->assertRedirect();
        $checkout = BillingCheckoutSession::query()->where('company_id', $company->id)->sole();
        $this->actingAs($admin)->post(route('admin.billing.fake.complete', $checkout))->assertRedirect();

        return [$company->fresh(), $admin];
    }

    /** @param array<string, scalar|null> $data */
    private function fakeEvent(string $type, string $objectType, string $objectId, array $data, ?int $created = null): array
    {
        $gateway = app(FakeBillingGateway::class);
        [$payload, $signature] = $gateway->signedEvent($type, $objectType, $objectId, $data, created: $created);

        return app(BillingWebhookProcessor::class)->process('fake', $payload, $signature);
    }

    private function stripeSignature(string $payload, string $secret): string
    {
        $timestamp = now()->timestamp;

        return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
    }

    /** @return array<string, mixed> */
    private function stripeSubscriptionPayload(): array
    {
        return [
            'id' => 'sub_test_phase3', 'customer' => 'cus_test_phase3', 'status' => 'active',
            'current_period_start' => now()->timestamp, 'current_period_end' => now()->addMonth()->timestamp,
            'cancel_at_period_end' => true,
            'items' => ['data' => [['id' => 'si_test_phase3', 'price' => ['id' => 'price_test_service_launch']]]],
        ];
    }
}
