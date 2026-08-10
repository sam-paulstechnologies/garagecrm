<?php

namespace Tests\Feature;

use App\Commercial\Plans;
use App\Commercial\SubscriptionManager;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingNumberClaim;
use App\Messaging\Models\MessagingPhoneNumber;
use App\Messaging\WhatsApp\WebhookRouter;
use App\Models\System\Company;
use App\Models\User;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppPendingNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CommercialFoundationSeeder::class);
        config([
            'messaging.providers.meta_whatsapp.app_id' => null,
            'messaging.providers.meta_whatsapp.app_secret' => null,
            'messaging.providers.meta_whatsapp.business_app_config_id' => null,
            'messaging.providers.meta_whatsapp.cloud_api_config_id' => null,
        ]);
    }

    public function test_free_can_add_first_number_and_it_survives_absent_meta_configuration(): void
    {
        [, $admin] = $this->tenant(Plans::FREE, 'Free Number Garage');

        $this->actingAs($admin)->post(route('admin.messaging.whatsapp.number.store'), $this->numberPayload())
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('messaging_number_claims', [
            'company_id' => $admin->company_id,
            'phone_e164' => '+971501234567',
            'connection_mode' => 'business_app_onboarding',
            'status' => MessagingNumberClaim::PENDING_META,
            'messaging_phone_number_id' => null,
        ]);
        $this->assertDatabaseCount('messaging_connections', 0);
        $this->assertDatabaseCount('messaging_phone_numbers', 0);

        $this->actingAs($admin)->get(route('admin.messaging.whatsapp.index'))
            ->assertOk()
            ->assertSee('+971501234567')
            ->assertSee('WhatsApp number added.')
            ->assertSee('Meta connection is not configured for this staging environment yet.')
            ->assertSee('Pending Meta verification');
    }

    public function test_free_cannot_add_a_second_number_and_keeps_the_first(): void
    {
        [, $admin] = $this->tenant(Plans::FREE, 'Free Limit Garage');
        $this->actingAs($admin)->post(route('admin.messaging.whatsapp.number.store'), $this->numberPayload())->assertRedirect();

        $this->actingAs($admin)->from(route('admin.messaging.whatsapp.index'))
            ->post(route('admin.messaging.whatsapp.number.store'), $this->numberPayload('55 999 0000'))
            ->assertRedirect(route('admin.messaging.whatsapp.index'))
            ->assertSessionHasErrors('plan');

        $this->assertDatabaseCount('messaging_number_claims', 1);
        $this->assertDatabaseHas('messaging_number_claims', ['phone_e164' => '+971501234567']);
    }

    public function test_service_uses_its_configured_one_number_allowance(): void
    {
        [$company, $admin] = $this->tenant(Plans::SERVICE, 'Service Limit Garage');
        $this->assertSame(1, app(\App\Commercial\EntitlementService::class)->limit($company, 'limit.whatsapp_numbers'));

        $this->actingAs($admin)->post(route('admin.messaging.whatsapp.number.store'), $this->numberPayload())->assertRedirect();
        $this->actingAs($admin)->from(route('admin.messaging.whatsapp.index'))
            ->post(route('admin.messaging.whatsapp.number.store'), $this->numberPayload('55 999 0000'))
            ->assertSessionHasErrors('plan');

        $this->assertDatabaseCount('messaging_number_claims', 1);
    }

    public function test_duplicate_submission_is_idempotent_but_another_tenant_cannot_claim_the_number(): void
    {
        [, $admin] = $this->tenant(Plans::FREE, 'First Claim Garage');
        $this->actingAs($admin)->post(route('admin.messaging.whatsapp.number.store'), $this->numberPayload())->assertRedirect();
        $this->actingAs($admin)->post(route('admin.messaging.whatsapp.number.store'), $this->numberPayload())->assertRedirect();
        $this->assertDatabaseCount('messaging_number_claims', 1);

        [, $otherAdmin] = $this->tenant(Plans::FREE, 'Second Claim Garage');
        $this->actingAs($otherAdmin)->from(route('admin.messaging.whatsapp.index'))
            ->post(route('admin.messaging.whatsapp.number.store'), $this->numberPayload())
            ->assertSessionHasErrors('phone_number');
        $this->assertDatabaseCount('messaging_number_claims', 1);
    }

    public function test_cross_tenant_edit_and_delete_are_not_available(): void
    {
        [, $owner] = $this->tenant(Plans::FREE, 'Claim Owner Garage');
        $this->actingAs($owner)->post(route('admin.messaging.whatsapp.number.store'), $this->numberPayload())->assertRedirect();
        $claim = MessagingNumberClaim::query()->sole();
        [, $otherAdmin] = $this->tenant(Plans::FREE, 'Claim Attacker Garage');

        $this->actingAs($otherAdmin)->patch(route('admin.messaging.whatsapp.number.update', $claim), $this->numberPayload('52 000 0000'))
            ->assertNotFound();
        $this->actingAs($otherAdmin)->delete(route('admin.messaging.whatsapp.number.destroy', $claim))
            ->assertNotFound();

        $this->assertDatabaseHas('messaging_number_claims', ['id' => $claim->id, 'company_id' => $owner->company_id]);
    }

    public function test_tenant_input_cannot_set_provider_identifiers_or_connected_status(): void
    {
        [, $admin] = $this->tenant(Plans::FREE, 'Provider Boundary Garage');
        $payload = $this->numberPayload() + [
            'status' => 'ready',
            'waba_id' => 'tenant-supplied-waba',
            'phone_number_id' => 'tenant-supplied-phone',
            'messaging_phone_number_id' => 999,
        ];
        $this->actingAs($admin)->post(route('admin.messaging.whatsapp.number.store'), $payload)->assertRedirect();

        $claim = MessagingNumberClaim::query()->sole();
        $this->assertSame(MessagingNumberClaim::PENDING_META, $claim->status);
        $this->assertNull($claim->messaging_phone_number_id);
        $this->assertDatabaseCount('messaging_connections', 0);
        $this->assertDatabaseCount('messaging_phone_numbers', 0);
    }

    public function test_meta_cta_is_available_only_when_the_selected_mode_is_configured(): void
    {
        [, $admin] = $this->tenant(Plans::FREE, 'Config Guard Garage');
        $this->actingAs($admin)->post(route('admin.messaging.whatsapp.number.store'), $this->numberPayload())->assertRedirect();

        $this->actingAs($admin)->get(route('admin.messaging.whatsapp.index'))
            ->assertOk()
            ->assertSee('Meta connection is not configured for this staging environment yet.')
            ->assertDontSee('data-connect-mode=', false);

        config([
            'messaging.providers.meta_whatsapp.app_id' => 'test-app-id',
            'messaging.providers.meta_whatsapp.app_secret' => 'test-app-secret',
            'messaging.providers.meta_whatsapp.business_app_config_id' => 'test-business-config',
        ]);

        $this->actingAs($admin)->get(route('admin.messaging.whatsapp.index'))
            ->assertOk()
            ->assertSee('Connect with Meta')
            ->assertSee('data-connect-mode="business_app_onboarding"', false);
    }

    public function test_pending_number_never_participates_in_inbound_provider_routing(): void
    {
        [, $admin] = $this->tenant(Plans::FREE, 'Pending Routing Garage');
        $this->actingAs($admin)->post(route('admin.messaging.whatsapp.number.store'), $this->numberPayload())->assertRedirect();

        $this->assertNull(app(WebhookRouter::class)->resolve('unverified-waba', '+971501234567'));
        $this->assertDatabaseCount('messaging_connections', 0);
        $this->assertDatabaseCount('messaging_phone_numbers', 0);
    }

    public function test_unverified_number_can_be_updated_removed_and_replaced_safely(): void
    {
        [, $admin] = $this->tenant(Plans::FREE, 'Replace Number Garage');
        $this->actingAs($admin)->post(route('admin.messaging.whatsapp.number.store'), $this->numberPayload())->assertRedirect();
        $claim = MessagingNumberClaim::query()->sole();

        $this->actingAs($admin)->patch(route('admin.messaging.whatsapp.number.update', $claim), $this->numberPayload('55 111 2222'))
            ->assertRedirect();
        $this->assertSame('+971551112222', $claim->fresh()->phone_e164);

        $this->actingAs($admin)->delete(route('admin.messaging.whatsapp.number.destroy', $claim))->assertRedirect();
        $this->assertDatabaseCount('messaging_number_claims', 0);

        $this->actingAs($admin)->post(route('admin.messaging.whatsapp.number.store'), $this->numberPayload('56 333 4444'))->assertRedirect();
        $this->assertDatabaseHas('messaging_number_claims', ['phone_e164' => '+971563334444']);
    }

    public function test_existing_verified_connection_is_not_changed_by_pending_number_feature(): void
    {
        [$company, $admin] = $this->tenant(Plans::FREE, 'Verified Regression Garage');
        $connection = MessagingConnection::query()->create([
            'company_id' => $company->id,
            'product_key' => 'sayaraforce',
            'provider' => 'meta_whatsapp',
            'status' => 'connected',
            'connection_mode' => 'business_app_onboarding',
            'waba_id' => 'verified-waba',
        ]);
        $phone = MessagingPhoneNumber::query()->create([
            'messaging_connection_id' => $connection->id,
            'provider' => 'meta_whatsapp',
            'phone_number_id' => 'verified-provider-phone',
            'display_phone_number' => '+971 50 765 4321',
            'phone_e164' => '+971507654321',
            'registration_status' => 'VERIFIED',
            'is_primary' => true,
        ]);

        $this->actingAs($admin)->get(route('admin.messaging.whatsapp.index'))
            ->assertOk()
            ->assertSee('WhatsApp is connected')
            ->assertSee('+971 50 765 4321');

        $this->assertDatabaseHas('messaging_phone_numbers', ['id' => $phone->id, 'phone_number_id' => 'verified-provider-phone']);
        $this->assertDatabaseCount('messaging_number_claims', 0);
    }

    private function tenant(string $plan, string $name): array
    {
        $company = Company::query()->create(['name' => $name, 'status' => 'active']);
        app(SubscriptionManager::class)->assignPlan($company, $plan);
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
            'status' => true,
            'must_change_password' => false,
        ]);

        return [$company, $admin];
    }

    private function numberPayload(string $number = '050 123 4567'): array
    {
        return [
            'country_code' => '+971',
            'phone_number' => $number,
            'label' => 'Main service desk',
            'connection_mode' => 'business_app_onboarding',
        ];
    }
}
