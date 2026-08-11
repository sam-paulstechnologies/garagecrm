<?php

namespace Tests\Feature;

use App\Billing\BillingGatewayResolver;
use App\Billing\BillingWebhookProcessor;
use App\Billing\Exceptions\BillingConfigurationException;
use App\Billing\Gateways\FakeBillingGateway;
use App\Billing\Gateways\StripeBillingGateway;
use App\Commercial\EntitlementService;
use App\Commercial\Plans;
use App\Commercial\SubscriptionManager;
use App\Jobs\TransitionIntroductoryBillingPrice;
use App\Models\Commercial\BillingCheckoutSession;
use App\Models\Commercial\BillingInvoice;
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

    public function test_checkout_redirect_cannot_activate_and_verified_fake_payment_lifecycle_activates_once(): void
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
        $this->assertDatabaseCount('billing_provider_events', 2);
        $this->assertDatabaseHas('billing_invoices', [
            'company_id' => $company->id,
            'billing_checkout_session_id' => $checkout->id,
            'price_id' => $service->id,
            'payment_provider' => 'fake',
            'test_mode' => true,
            'status' => 'paid',
            'currency' => 'AED',
            'amount_paid' => '199.00',
        ]);
        $this->actingAs($admin)->get(route('admin.billing.index'))
            ->assertOk()
            ->assertSeeText('Test')
            ->assertSeeText('fake')
            ->assertSeeText('Service')
            ->assertSeeText('AED 199.00');

        $this->travel(2)->seconds();
        $this->actingAs($admin)->post(route('admin.billing.fake.complete', $checkout))->assertRedirect();
        $this->assertDatabaseCount('billing_provider_events', 2);
        $this->assertDatabaseCount('billing_invoices', 1);
        $this->assertSame(1, $subscription->fresh()->introductory_cycles_completed);
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_verified_checkout_event_alone_remains_payment_pending_and_does_not_unlock_entitlements(): void
    {
        [$company, $admin] = $this->tenant(Plans::FREE, 'Checkout Pending Garage');
        $service = Price::query()->where('code', 'service:2026-launch-v1:aed-monthly')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.billing.checkout'), [
            'price_code' => $service->code,
            'idempotency_key' => 'checkout-pending-00000000000001',
        ])->assertRedirect();
        $checkout = BillingCheckoutSession::query()->sole();
        $mapping = PriceProviderMapping::query()
            ->where('price_id', $service->id)->where('payment_provider', 'fake')->firstOrFail();

        $this->fakeEvent('checkout.session.completed', 'checkout.session', (string) $checkout->provider_checkout_id, [
            'local_checkout_id' => $checkout->id,
            'provider_customer_id' => $checkout->provider_customer_id,
            'provider_subscription_id' => 'sub_fake_pending_only',
            'provider_price_id' => $mapping->provider_price_id,
            'payment_status' => 'paid',
        ], eventId: 'evt_fake_checkout_pending_only');

        $subscription = $company->fresh()->subscription;
        $this->assertSame(Plans::FREE, $subscription->planVersion->plan->code);
        $this->assertSame('active', $subscription->status);
        $this->assertSame('not_required', $subscription->payment_status);
        $this->assertSame('payment_pending', $checkout->fresh()->status);
        $this->assertFalse(app(EntitlementService::class)->can($company, 'transactional_booking_reminders'));
        $this->assertDatabaseCount('billing_invoices', 0);
    }

    public function test_failed_initial_payment_records_failure_without_activating_or_creating_paid_invoice(): void
    {
        [$company, $admin] = $this->tenant(Plans::FREE, 'Failed Checkout Garage');
        $service = Price::query()->where('code', 'service:2026-launch-v1:aed-monthly')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.billing.checkout'), [
            'price_code' => $service->code,
            'idempotency_key' => 'checkout-failed-000000000000001',
        ])->assertRedirect();
        $checkout = BillingCheckoutSession::query()->sole();
        $mapping = PriceProviderMapping::query()
            ->where('price_id', $service->id)->where('payment_provider', 'fake')->firstOrFail();
        $provider = [
            'local_checkout_id' => $checkout->id,
            'provider_customer_id' => $checkout->provider_customer_id,
            'provider_subscription_id' => 'sub_fake_failed_initial',
            'provider_price_id' => $mapping->provider_price_id,
        ];
        $this->fakeEvent('checkout.session.completed', 'checkout.session', (string) $checkout->provider_checkout_id,
            $provider + ['payment_status' => 'unpaid'], eventId: 'evt_fake_failed_checkout');
        $this->fakeEvent('invoice.payment_failed', 'invoice', 'in_fake_failed_initial', $provider + [
            'provider_invoice_id' => 'in_fake_failed_initial',
            'currency' => 'aed',
            'amount_due_minor' => 19900,
            'amount_paid_minor' => 0,
            'period_start' => now()->timestamp,
            'period_end' => now()->addMonth()->timestamp,
        ], eventId: 'evt_fake_failed_invoice');

        $subscription = $company->fresh()->subscription;
        $this->assertSame(Plans::FREE, $subscription->planVersion->plan->code);
        $this->assertSame('active', $subscription->status);
        $this->assertSame('not_required', $subscription->payment_status);
        $this->assertSame('payment_failed', $checkout->fresh()->status);
        $this->assertDatabaseHas('billing_invoices', [
            'provider_invoice_id' => 'in_fake_failed_initial',
            'status' => 'payment_failed',
            'amount_paid' => '0.00',
        ]);
        $this->assertSame(0, BillingInvoice::query()->where('status', 'paid')->count());
    }

    public function test_cancelled_fake_checkout_never_activates_the_requested_plan(): void
    {
        [$company, $admin] = $this->tenant(Plans::FREE, 'Cancelled Checkout Garage');
        $service = Price::query()->where('code', 'service:2026-launch-v1:aed-monthly')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.billing.checkout'), [
            'price_code' => $service->code,
            'idempotency_key' => 'checkout-cancelled-000000000001',
        ])->assertRedirect();
        $checkout = BillingCheckoutSession::query()->sole();

        $this->actingAs($admin)->post(route('admin.billing.fake.cancel', $checkout))
            ->assertRedirect(route('admin.billing.index'))
            ->assertSessionHas('warning');

        $this->assertSame('cancelled', $checkout->fresh()->status);
        $this->assertSame(Plans::FREE, $company->fresh()->subscription->planVersion->plan->code);
        $this->assertDatabaseCount('billing_provider_events', 0);
        $this->assertDatabaseCount('billing_invoices', 0);
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
            'id' => 'evt_stripe_replay_1', 'object' => 'event',
            'api_version' => '2026-07-29.dahlia',
            'type' => 'customer.updated', 'created' => now()->timestamp,
            'livemode' => false,
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

    public function test_stripe_webhook_rejects_live_mode_event_even_with_a_valid_signature(): void
    {
        config()->set([
            'billing.mode' => 'test',
            'billing.stripe.secret_key' => 'sk_test_sandbox_guard',
            'billing.stripe.publishable_key' => 'pk_test_sandbox_guard',
            'billing.stripe.webhook_secret' => 'whsec_sandbox_guard',
        ]);
        $payload = json_encode([
            'id' => 'evt_live_forbidden',
            'type' => 'invoice.paid',
            'created' => now()->timestamp,
            'livemode' => true,
            'data' => ['object' => ['id' => 'in_live_forbidden', 'object' => 'invoice']],
        ], JSON_THROW_ON_ERROR);

        $this->call('POST', route('billing.webhook', ['provider' => 'stripe']), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($payload, 'whsec_sandbox_guard'),
        ], $payload)->assertStatus(400)->assertJson(['received' => false]);

        $this->assertDatabaseCount('billing_provider_events', 0);
    }

    public function test_stripe_adapter_refuses_live_publishable_key_even_with_test_secret_key(): void
    {
        config()->set([
            'billing.mode' => 'test',
            'billing.stripe.secret_key' => 'sk_test_sandbox_guard',
            'billing.stripe.publishable_key' => 'pk_live_forbidden',
        ]);
        Http::fake();

        $this->expectException(BillingConfigurationException::class);
        app(StripeBillingGateway::class)->createCustomer(
            Company::query()->create(['name' => 'No Live Publishable Key', 'status' => 'active']),
            'stripe-publishable-live-refusal',
        );
    }

    public function test_stripe_adapter_refuses_live_secret_key_in_test_mode(): void
    {
        config()->set([
            'billing.mode' => 'test',
            'billing.stripe.secret_key' => 'sk_live_forbidden',
        ]);
        Http::fake();

        $this->expectException(BillingConfigurationException::class);
        app(StripeBillingGateway::class)->createCustomer(
            Company::query()->create(['name' => 'No Live Secret Key', 'status' => 'active']),
            'stripe-secret-live-refusal',
        );
    }

    public function test_stripe_webhook_refuses_non_test_billing_mode_before_recording_event(): void
    {
        config()->set([
            'billing.mode' => 'live',
            'billing.stripe.secret_key' => 'sk_test_sandbox_guard',
            'billing.stripe.webhook_secret' => 'whsec_sandbox_guard',
        ]);
        $payload = json_encode([
            'id' => 'evt_test_in_live_config_forbidden',
            'type' => 'customer.updated',
            'created' => now()->timestamp,
            'livemode' => false,
            'data' => ['object' => ['id' => 'cus_test_forbidden', 'object' => 'customer']],
        ], JSON_THROW_ON_ERROR);

        $this->call('POST', route('billing.webhook', ['provider' => 'stripe']), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $this->stripeSignature($payload, 'whsec_sandbox_guard'),
        ], $payload)->assertStatus(503)->assertJson(['received' => false]);

        $this->assertDatabaseCount('billing_provider_events', 0);
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

    public function test_fake_and_stripe_adapters_normalize_the_same_checkout_and_invoice_contract(): void
    {
        $fake = app(FakeBillingGateway::class);
        $stripe = app(StripeBillingGateway::class);
        $now = now()->timestamp;
        $commonCheckout = [
            'local_checkout_id' => 42,
            'provider_customer_id' => 'cus_test_contract',
            'provider_subscription_id' => 'sub_test_contract',
            'provider_price_id' => 'price_testContract001',
            'payment_status' => 'paid',
        ];
        [$fakeCheckoutPayload] = $fake->signedEvent(
            'checkout.session.completed', 'checkout.session', 'cs_test_contract', $commonCheckout,
            'evt_fake_contract_checkout', $now,
        );
        $stripeCheckoutPayload = json_encode([
            'id' => 'evt_stripe_contract_checkout',
            'object' => 'event',
            'api_version' => '2026-07-29.dahlia',
            'type' => 'checkout.session.completed',
            'created' => $now,
            'livemode' => false,
            'data' => ['object' => [
                'id' => 'cs_test_contract',
                'object' => 'checkout.session',
                'customer' => 'cus_test_contract',
                'subscription' => 'sub_test_contract',
                'payment_status' => 'paid',
                'metadata' => ['local_checkout_id' => 42, 'provider_price_id' => 'price_testContract001'],
            ]],
        ], JSON_THROW_ON_ERROR);
        $this->assertNormalizedContractMatches(
            $fake->normalizeEvent($fakeCheckoutPayload),
            $stripe->normalizeEvent($stripeCheckoutPayload),
            array_keys($commonCheckout + ['test_mode' => true]),
        );

        $commonInvoice = [
            'local_checkout_id' => 42,
            'provider_customer_id' => 'cus_test_contract',
            'provider_subscription_id' => 'sub_test_contract',
            'provider_price_id' => 'price_testContract001',
            'provider_invoice_id' => 'in_test_contract',
            'payment_status' => 'paid',
            'currency' => 'aed',
            'amount_due_minor' => 19900,
            'amount_paid_minor' => 19900,
            'period_start' => $now,
            'period_end' => $now + 2592000,
            'paid_at' => $now,
        ];
        [$fakeInvoicePayload] = $fake->signedEvent(
            'invoice.paid', 'invoice', 'in_test_contract', $commonInvoice,
            'evt_fake_contract_invoice', $now,
        );
        $stripeInvoicePayload = json_encode([
            'id' => 'evt_stripe_contract_invoice',
            'object' => 'event',
            'api_version' => '2026-07-29.dahlia',
            'type' => 'invoice.paid',
            'created' => $now,
            'livemode' => false,
            'data' => ['object' => [
                'id' => 'in_test_contract',
                'object' => 'invoice',
                'customer' => 'cus_test_contract',
                'subscription' => 'sub_test_contract',
                'status' => 'paid',
                'currency' => 'aed',
                'amount_due' => 19900,
                'amount_paid' => 19900,
                'status_transitions' => ['paid_at' => $now],
                'lines' => ['data' => [[
                    'price' => ['id' => 'price_testContract001'],
                    'period' => ['start' => $now, 'end' => $now + 2592000],
                ]]],
                'parent' => ['subscription_details' => [
                    'subscription' => 'sub_test_contract',
                    'metadata' => ['local_checkout_id' => 42, 'provider_price_id' => 'price_testContract001'],
                ]],
            ]],
        ], JSON_THROW_ON_ERROR);
        $this->assertNormalizedContractMatches(
            $fake->normalizeEvent($fakeInvoicePayload),
            $stripe->normalizeEvent($stripeInvoicePayload),
            array_keys($commonInvoice + ['test_mode' => true]),
        );
    }

    public function test_legacy_fake_paid_checkout_reconciliation_uses_a_verified_idempotent_invoice_event(): void
    {
        [$company] = $this->activatedServiceTenant();
        $checkout = BillingCheckoutSession::query()->where('company_id', $company->id)->sole();
        BillingInvoice::query()->delete();
        $company->subscription()->update(['introductory_cycles_completed' => 0]);

        $this->artisan('billing:reconcile-fake-test-history', ['--confirm' => true])->assertSuccessful();
        $this->assertDatabaseHas('billing_invoices', [
            'billing_checkout_session_id' => $checkout->id,
            'price_id' => $checkout->requested_price_id,
            'payment_provider' => 'fake',
            'test_mode' => true,
            'status' => 'paid',
        ]);
        $this->assertSame(1, $company->fresh()->subscription->introductory_cycles_completed);

        $this->artisan('billing:reconcile-fake-test-history', ['--confirm' => true])->assertSuccessful();
        $this->assertDatabaseCount('billing_invoices', 1);
        $this->assertSame(1, $company->fresh()->subscription->introductory_cycles_completed);
    }

    public function test_introductory_price_transition_is_deterministic_and_idempotent(): void
    {
        [$company] = $this->activatedServiceTenant();
        $subscription = $company->fresh()->subscription;
        $subscription->update(['introductory_cycles_completed' => 3]);

        $job = new TransitionIntroductoryBillingPrice($subscription->id);
        $job->handle(app(BillingGatewayResolver::class));
        $this->assertNotNull($subscription->fresh()->standard_price_transition_requested_at);
        $this->assertNotNull($subscription->fresh()->launch_offer_consumed_at);
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
        $this->assertSame(2, $subscription->fresh()->introductory_cycles_completed);
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
    private function fakeEvent(
        string $type,
        string $objectType,
        string $objectId,
        array $data,
        ?int $created = null,
        ?string $eventId = null,
    ): array {
        $gateway = app(FakeBillingGateway::class);
        [$payload, $signature] = $gateway->signedEvent($type, $objectType, $objectId, $data, $eventId, $created);

        return app(BillingWebhookProcessor::class)->process('fake', $payload, $signature);
    }

    private function assertNormalizedContractMatches(
        \App\Billing\Data\NormalizedBillingEvent $fake,
        \App\Billing\Data\NormalizedBillingEvent $stripe,
        array $keys,
    ): void {
        $this->assertSame($fake->type, $stripe->type);
        $this->assertSame($fake->objectType, $stripe->objectType);
        $this->assertSame($fake->objectId, $stripe->objectId);
        $this->assertEquals(
            collect($fake->data)->only($keys)->all(),
            collect($stripe->data)->only($keys)->all(),
        );
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
