<?php

namespace Tests\Feature;

use App\Billing\BillingGatewayResolver;
use App\Billing\BillingManager;
use App\Billing\BillingWebhookProcessor;
use App\Billing\Contracts\BillingGateway;
use App\Billing\Gateways\FakeBillingGateway;
use App\Commercial\LaunchOfferService;
use App\Commercial\Plans;
use App\Commercial\SubscriptionManager;
use App\Models\Commercial\CommercialSetting;
use App\Models\Commercial\Price;
use App\Models\Commercial\PriceProviderMapping;
use App\Models\System\Company;
use App\Models\User;
use Database\Seeders\BillingFoundationSeeder;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class LaunchOfferCommercialPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set([
            'billing.provider' => 'fake',
            'billing.mode' => 'test',
            'billing.checkout_enabled' => true,
            'billing.fake.webhook_secret' => 'launch-offer-test-secret',
            'queue.default' => 'sync',
        ]);
        $this->seed(CommercialFoundationSeeder::class);
        $this->seed(BillingFoundationSeeder::class);
    }

    public function test_launch_offer_on_selects_each_canonical_launch_mapping(): void
    {
        $this->assertTrue(app(LaunchOfferService::class)->isEnabled());

        foreach ([Plans::SERVICE, Plans::GROWTH, Plans::PERFORMANCE, Plans::AI_PRO] as $plan) {
            [$company] = $this->tenant(Plans::FREE, 'Launch '.str($plan)->headline().' '.uniqid());
            $price = $this->price($plan);
            $checkout = app(BillingManager::class)->startCheckout($company, $price, 'launch-'.bin2hex(random_bytes(20)));
            $mapping = $this->mapping($price, 'launch');

            $this->assertSame('launch', $checkout->price_phase);
            $this->assertTrue($checkout->launch_offer_qualified);
            $this->assertSame('launch', $mapping->price_phase);
        }
    }

    public function test_launch_offer_off_selects_each_canonical_standard_mapping(): void
    {
        CommercialSetting::query()->where('key', LaunchOfferService::SETTING_KEY)
            ->update(['boolean_value' => false]);

        foreach ([Plans::SERVICE, Plans::GROWTH, Plans::PERFORMANCE, Plans::AI_PRO] as $plan) {
            [$company] = $this->tenant(Plans::FREE, 'Standard '.str($plan)->headline().' '.uniqid());
            $price = $this->price($plan);
            $checkout = app(BillingManager::class)->startCheckout($company, $price, 'standard-'.bin2hex(random_bytes(20)));
            $mapping = $this->mapping($price, 'standard');

            $this->assertSame('standard', $checkout->price_phase);
            $this->assertFalse($checkout->launch_offer_qualified);
            $this->assertSame('standard', $mapping->price_phase);
        }
    }

    public function test_platform_admin_controls_future_eligibility_and_tenant_is_forbidden(): void
    {
        [, $tenantAdmin] = $this->tenant(Plans::FREE, 'Tenant Control');
        $platform = User::query()->create([
            'name' => 'Platform Commercial Admin',
            'email' => 'platform-commercial@example.test',
            'password' => 'Secure-Password-2026!',
            'role' => 'super_admin',
            'status' => true,
            'must_change_password' => false,
        ]);

        $this->actingAs($tenantAdmin)->patch(route('super-admin.commercial.launch-offer.update'), ['enabled' => false])
            ->assertForbidden();
        $this->assertTrue(app(LaunchOfferService::class)->isEnabled());

        $this->actingAs($platform)->patch(route('super-admin.commercial.launch-offer.update'), ['enabled' => false])
            ->assertRedirect();
        $this->assertFalse(app(LaunchOfferService::class)->isEnabled());
        $this->assertDatabaseHas('entitlement_audit_logs', [
            'actor_id' => $platform->id,
            'event' => 'commercial.launch_offer_changed',
            'source' => 'platform_admin',
        ]);

        $this->actingAs($platform)->get(route('super-admin.commercial.index'))
            ->assertOk()
            ->assertSeeText('Launch Offer: OFF')
            ->assertSeeText('3 successful paid monthly cycles');
    }

    public function test_public_pricing_reflects_offer_state_without_hard_coded_discount(): void
    {
        $this->get('/')->assertOk()
            ->assertSeeText('AED 199')
            ->assertSeeText('first 3 successful paid monthly cycles');

        CommercialSetting::query()->where('key', LaunchOfferService::SETTING_KEY)
            ->update(['boolean_value' => false]);

        $this->get('/')->assertOk()
            ->assertSeeText('AED 399')
            ->assertSeeText('New subscriptions use the standard monthly catalogue prices.')
            ->assertDontSeeText('Launch price');
    }

    public function test_existing_launch_subscription_keeps_three_paid_cycles_when_global_offer_turns_off(): void
    {
        [$company, $admin] = $this->tenant(Plans::FREE, 'Three Cycle Garage');
        $service = $this->price(Plans::SERVICE);
        $checkout = app(BillingManager::class)->startCheckout(
            $company,
            $service,
            'three-cycle-'.bin2hex(random_bytes(20)),
        );
        $this->actingAs($admin)->post(route('admin.billing.fake.complete', $checkout))->assertRedirect();
        $subscription = $company->fresh()->subscription;
        $this->assertSame(1, $subscription->introductory_cycles_completed);
        $this->assertNotNull($subscription->launch_offer_qualified_at);

        CommercialSetting::query()->where('key', LaunchOfferService::SETTING_KEY)
            ->update(['boolean_value' => false]);

        $this->paidInvoice($subscription->fresh(), 'cycle_two', 19900);
        $this->assertSame(2, $subscription->fresh()->introductory_cycles_completed);
        $this->assertNull($subscription->fresh()->standard_price_transition_requested_at);
        $this->paidInvoice($subscription->fresh(), 'cycle_two_distinct_event', 19900, 'cycle_two');
        $this->assertSame(2, $subscription->fresh()->introductory_cycles_completed);

        $this->paidInvoice($subscription->fresh(), 'cycle_three', 19900);
        $subscription->refresh();
        $this->assertSame(3, $subscription->introductory_cycles_completed);
        $this->assertNotNull($subscription->standard_price_transition_requested_at);
        $this->assertNotNull($subscription->launch_offer_consumed_at);
        $this->assertSame($this->mapping($service, 'standard')->provider_price_id, $subscription->provider_price_id);

        $this->paidInvoice($subscription->fresh(), 'cycle_four', 39900);
        $this->assertSame(3, $subscription->fresh()->introductory_cycles_completed);

        CommercialSetting::query()->where('key', LaunchOfferService::SETTING_KEY)
            ->update(['boolean_value' => true]);
        $growthCheckout = app(BillingManager::class)->startCheckout(
            $company->fresh(),
            $this->price(Plans::GROWTH),
            'post-consumption-'.bin2hex(random_bytes(20)),
        );
        $this->assertSame('standard', $growthCheckout->price_phase);
        $this->assertFalse($growthCheckout->launch_offer_qualified);
    }

    public function test_checkout_failure_expiry_and_duplicate_paid_event_do_not_overcount(): void
    {
        [$company] = $this->tenant(Plans::FREE, 'No Phantom Cycles');
        $checkout = app(BillingManager::class)->startCheckout(
            $company,
            $this->price(Plans::SERVICE),
            'no-phantom-'.bin2hex(random_bytes(20)),
        );
        $this->assertSame(0, $company->fresh()->subscription->introductory_cycles_completed);
        $this->assertNull($company->fresh()->subscription->launch_offer_qualified_at);

        $checkout->update(['status' => 'expired']);
        $this->assertSame(0, $company->fresh()->subscription->introductory_cycles_completed);
        $this->assertNull($company->fresh()->subscription->launch_offer_qualified_at);
    }

    public function test_failed_provider_transition_keeps_recoverable_introductory_state(): void
    {
        [$company, $admin] = $this->tenant(Plans::FREE, 'Retryable Transition Garage');
        $checkout = app(BillingManager::class)->startCheckout(
            $company,
            $this->price(Plans::SERVICE),
            'retryable-transition-'.bin2hex(random_bytes(16)),
        );
        $this->actingAs($admin)->post(route('admin.billing.fake.complete', $checkout))->assertRedirect();
        $subscription = $company->fresh()->subscription;
        $subscription->update(['introductory_cycles_completed' => 3]);

        $gateway = \Mockery::mock(BillingGateway::class);
        $gateway->shouldReceive('changeSubscription')->once()->andThrow(new RuntimeException('Synthetic provider failure'));
        $resolver = \Mockery::mock(BillingGatewayResolver::class);
        $resolver->shouldReceive('for')->once()->with('fake')->andReturn($gateway);

        try {
            (new \App\Jobs\TransitionIntroductoryBillingPrice($subscription->id))->handle($resolver);
            $this->fail('The synthetic provider failure should be retryable.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Synthetic provider failure', $exception->getMessage());
        }

        $subscription->refresh();
        $this->assertNull($subscription->standard_price_transition_requested_at);
        $this->assertNull($subscription->launch_offer_consumed_at);
        $this->assertSame(3, $subscription->introductory_cycles_completed);
        $this->assertDatabaseMissing('entitlement_audit_logs', [
            'subscription_id' => $subscription->id,
            'event' => 'billing.standard_price_transition_requested',
        ]);
    }

    private function paidInvoice(
        \App\Models\Commercial\Subscription $subscription,
        string $eventSuffix,
        int $amountMinor,
        ?string $invoiceSuffix = null,
    ): void {
        $gateway = app(FakeBillingGateway::class);
        $created = now()->addMinutes($subscription->introductory_cycles_completed + 1)->timestamp;
        $invoiceId = 'in_fake_'.($invoiceSuffix ?? $eventSuffix);
        [$payload, $signature] = $gateway->signedEvent('invoice.paid', 'invoice', $invoiceId, [
            'provider_subscription_id' => $subscription->provider_subscription_id,
            'provider_price_id' => $subscription->provider_price_id,
            'provider_invoice_id' => $invoiceId,
            'currency' => 'AED',
            'amount_due_minor' => $amountMinor,
            'amount_paid_minor' => $amountMinor,
            'period_start' => $created,
            'period_end' => $created + 2_592_000,
            'paid_at' => $created,
            'test_mode' => true,
        ], 'evt_fake_'.$eventSuffix, $created);
        $result = app(BillingWebhookProcessor::class)->process('fake', $payload, $signature);
        $this->assertSame('processed', $result['status']);

        $duplicate = app(BillingWebhookProcessor::class)->process('fake', $payload, $signature);
        $this->assertTrue($duplicate['duplicate']);
    }

    private function price(string $plan): Price
    {
        return Price::query()->where('code', $plan.':2026-launch-v1:aed-monthly')->sole();
    }

    private function mapping(Price $price, string $phase): PriceProviderMapping
    {
        return PriceProviderMapping::query()
            ->where('price_id', $price->id)
            ->where('payment_provider', 'fake')
            ->where('price_phase', $phase)
            ->sole();
    }

    /** @return array{Company, User} */
    private function tenant(string $plan, string $name): array
    {
        $slug = str($name)->slug().'-'.bin2hex(random_bytes(2));
        $company = Company::query()->create([
            'name' => $name,
            'email' => $slug.'@example.test',
            'status' => 'active',
        ]);
        app(SubscriptionManager::class)->assignPlan($company, $plan);
        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => $name.' Admin',
            'email' => $slug.'-admin@example.test',
            'password' => 'Secure-Password-2026!',
            'role' => 'admin',
            'status' => true,
            'must_change_password' => false,
        ]);

        return [$company->fresh(), $user];
    }
}
