<?php

namespace Tests\Feature;

use App\Commercial\EntitlementService;
use App\Commercial\Plans;
use App\Models\Commercial\BillingCheckoutSession;
use App\Models\Commercial\Price;
use App\Models\Notifications\NotificationIntent;
use App\Models\System\Company;
use App\Models\User;
use App\Notifications\NotificationIntentService;
use Database\Seeders\BillingFoundationSeeder;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialLaunchRehearsalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'registration.public_enabled' => true,
            'billing.provider' => 'fake',
            'billing.mode' => 'test',
            'billing.checkout_enabled' => true,
            'billing.fake.webhook_secret' => 'synthetic-rehearsal-secret',
            'staging.communications.whatsapp_outbound_enabled' => false,
            'staging.communications.sms_outbound_enabled' => false,
            'mail.default' => 'log',
        ]);
        $this->seed(CommercialFoundationSeeder::class);
        $this->seed(BillingFoundationSeeder::class);
    }

    public function test_free_signup_and_verified_fake_upgrades_rehearse_the_complete_commercial_ladder(): void
    {
        $this->post('/register', [
            'garage_name' => 'Commercial Rehearsal Garage',
            'name' => 'Synthetic Rehearsal Admin',
            'email' => 'commercial-rehearsal@staging.sayaraforce.test',
            'phone' => '+971 50 000 0777',
            'address' => 'Synthetic Industrial Area, Dubai',
            'password' => 'Synthetic-Only-Password-2026!',
            'password_confirmation' => 'Synthetic-Only-Password-2026!',
            'terms' => '1',
        ])->assertRedirect(route('admin.messaging.whatsapp.index'));

        $company = Company::query()->sole();
        $admin = User::query()->sole();
        $entitlements = app(EntitlementService::class);

        $this->assertSame(Plans::FREE, $company->subscription->planVersion->plan->code);
        $this->assertTrue($entitlements->can($company, 'whatsapp_inbound'));
        $this->assertSame(25, $entitlements->limit($company, 'limit.ai_monitored_customers'));
        $this->assertFalse($entitlements->can($company, 'transactional_booking_reminders'));
        $this->assertDatabaseCount('messaging_connections', 0);
        $this->get(route('admin.messaging.whatsapp.index'))->assertOk();

        $this->upgradeThroughVerifiedFakeCheckout($admin, Plans::SERVICE);
        $company->refresh();
        $this->assertTrue($entitlements->can($company, 'transactional_booking_reminders'));
        $this->assertSame(300, $entitlements->limit($company, 'limit.ai_monitored_customers'));
        $this->assertFalse($entitlements->can($company, 'jobs'));

        $intent = app(NotificationIntentService::class)->record(
            $company,
            $admin,
            'new_enquiry',
            'Synthetic enquiry',
            'A safe rehearsal notification.',
            'commercial-rehearsal-notification',
            dispatch: false,
        );
        $this->assertSame('pending', $intent->state);
        $this->assertDatabaseCount('notification_intents', 1);

        $this->upgradeThroughVerifiedFakeCheckout($admin, Plans::GROWTH);
        $company->refresh();
        $this->assertTrue($entitlements->can($company, 'jobs'));
        $this->assertTrue($entitlements->can($company, 'source_attribution'));
        $this->assertFalse($entitlements->can($company, 'campaign_intelligence'));

        $this->upgradeThroughVerifiedFakeCheckout($admin, Plans::PERFORMANCE);
        $company->refresh();
        $this->assertTrue($entitlements->can($company, 'campaign_intelligence'));
        $this->assertTrue($entitlements->can($company, 'marketing_roi'));
        $this->assertFalse($entitlements->can($company, 'ai_action_execution'));

        $this->upgradeThroughVerifiedFakeCheckout($admin, Plans::AI_PRO);
        $company->refresh();
        $this->assertTrue($entitlements->can($company, 'ai_action_execution'));
        $this->assertSame('approval_required', $entitlements->decide($company, 'ai_action_execution')->mode);
        $this->assertFalse($entitlements->can($company, 'whatsapp_ai_autonomous'));
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseCount('billing_provider_events', 8);
        $this->assertDatabaseCount('billing_invoices', 4);
        $this->assertDatabaseCount('billing_checkout_sessions', 4);
        $this->assertDatabaseCount('messaging_connections', 0);
        $this->assertSame('log', config('mail.default'));
        $this->assertFalse((bool) config('staging.communications.whatsapp_outbound_enabled'));
        $this->assertFalse((bool) config('staging.communications.sms_outbound_enabled'));
        $this->assertSame(1, NotificationIntent::query()->where('company_id', $company->id)->count());
    }

    private function upgradeThroughVerifiedFakeCheckout(User $admin, string $planCode): void
    {
        $price = Price::query()->where('code', $planCode.':2026-launch-v1:aed-monthly')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.billing.checkout'), [
            'price_code' => $price->code,
            'idempotency_key' => 'rehearsal-'.$planCode.'-'.bin2hex(random_bytes(8)),
        ])->assertRedirect();

        $checkout = BillingCheckoutSession::query()
            ->where('company_id', $admin->company_id)
            ->latest('id')
            ->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.billing.fake.complete', $checkout))
            ->assertRedirect(route('admin.billing.success', ['checkout' => $checkout->id]));

        $this->assertSame(
            $planCode,
            $admin->company->fresh()->subscription->planVersion->plan->code,
        );
    }
}
