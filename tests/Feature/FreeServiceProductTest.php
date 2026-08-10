<?php

namespace Tests\Feature;

use App\Commercial\Plans;
use App\Commercial\OutboundEntitlementPolicy;
use App\Commercial\ResourceLimitService;
use App\Commercial\RouteCapabilityMap;
use App\Commercial\SubscriptionManager;
use App\Messaging\Models\MessagingConnection;
use App\Models\System\Company;
use App\Models\User;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FreeServiceProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CommercialFoundationSeeder::class);
    }

    public function test_free_core_routes_are_available_and_paid_operations_are_server_blocked(): void
    {
        $company = $this->companyOn(Plans::FREE, 'Free Route Garage');
        $user = $this->tenantUser($company, 'free-routes@example.test');

        $this->actingAs($user)->get(route('admin.clients.index'))->assertOk();
        $this->actingAs($user)->get(route('admin.jobs.index'))
            ->assertForbidden()
            ->assertSee("You've seen what you're missing. Now don't miss another booking.");
        $this->actingAs($user)->get(route('admin.invoices.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.marketing.campaigns.store'), [])->assertForbidden();
    }

    public function test_free_dashboard_is_basic_and_does_not_render_management_metrics(): void
    {
        $company = $this->companyOn(Plans::FREE, 'Free Dashboard Garage');
        $user = $this->tenantUser($company, 'free-dashboard@example.test');

        $response = $this->actingAs($user)->get(route('admin.dashboard'))->assertOk()->assertSee('See what your garage is missing');
        $this->assertSame(0, $response->viewData('metrics')['new_enquiries']);
        $this->assertFalse($response->viewData('serviceMode'));
        $response->assertDontSee('Revenue this month');
    }

    public function test_service_dashboard_is_available_without_growth_marketing_or_operations(): void
    {
        $company = $this->companyOn(Plans::SERVICE, 'Service Product Garage');
        $user = $this->tenantUser($company, 'service-product@example.test');

        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirect(route('admin.service-dashboard'));
        $this->actingAs($user)->get(route('admin.service-dashboard'))
            ->assertOk()
            ->assertSee('Service manager dashboard')
            ->assertSee('300');
        $this->actingAs($user)->get(route('admin.jobs.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.invoices.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.growth.journey-mapping.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.marketing.campaigns.index'))->assertForbidden();
    }

    public function test_user_allowance_is_enforced_from_real_tenant_user_count(): void
    {
        $company = $this->companyOn(Plans::SERVICE, 'Three User Service Garage');
        $admin = $this->tenantUser($company, 'limit-admin@example.test');

        foreach (['second', 'third'] as $position) {
            $this->actingAs($admin)->post(route('admin.users.store'), [
                'name' => ucfirst($position).' User',
                'email' => $position.'@example.test',
                'role' => 'manager',
                'password' => 'Strong-Password-2026!',
                'password_confirmation' => 'Strong-Password-2026!',
                'status' => 1,
            ])->assertRedirect(route('admin.users.index'));
        }

        $this->actingAs($admin)->from(route('admin.users.create'))->post(route('admin.users.store'), [
            'name' => 'Fourth User',
            'email' => 'fourth@example.test',
            'role' => 'manager',
            'password' => 'Strong-Password-2026!',
            'password_confirmation' => 'Strong-Password-2026!',
            'status' => 1,
        ])->assertRedirect(route('admin.users.create'))->assertSessionHasErrors('plan');

        $this->assertSame(3, User::query()->where('company_id', $company->id)->count());
    }

    public function test_free_plan_refuses_a_second_tenant_user_server_side(): void
    {
        $company = $this->companyOn(Plans::FREE, 'One User Free Garage');
        $admin = $this->tenantUser($company, 'free-limit-admin@example.test');

        $this->actingAs($admin)->from(route('admin.users.create'))->post(route('admin.users.store'), [
            'name' => 'Second Free User',
            'email' => 'second-free@example.test',
            'role' => 'manager',
            'password' => 'Strong-Password-2026!',
            'password_confirmation' => 'Strong-Password-2026!',
            'status' => 1,
        ])->assertRedirect(route('admin.users.create'))->assertSessionHasErrors('plan');

        $this->assertSame(1, User::query()->where('company_id', $company->id)->count());
    }

    public function test_whatsapp_number_limit_allows_reconnect_but_blocks_an_additional_number(): void
    {
        $company = $this->companyOn(Plans::FREE, 'One Number Garage');
        $connection = MessagingConnection::query()->create([
            'company_id' => $company->id,
            'product_key' => 'sayaraforce',
            'provider' => 'meta_whatsapp',
            'status' => 'connected',
            'connection_mode' => 'cloud_api',
        ]);
        $connection->phoneNumbers()->create([
            'provider' => 'meta_whatsapp',
            'phone_number_id' => 'synthetic-phone-one',
            'is_primary' => true,
        ]);

        $limits = app(ResourceLimitService::class);
        $limits->assertCanConnectPhone($company, 'meta_whatsapp', 'synthetic-phone-one');

        $this->expectException(ValidationException::class);
        $limits->assertCanConnectPhone($company, 'meta_whatsapp', 'synthetic-phone-two');
    }

    public function test_route_capability_map_separates_manual_transactional_marketing_and_ai_actions(): void
    {
        $map = app(RouteCapabilityMap::class);

        $this->assertSame('whatsapp_manual_reply', $map->capabilityFor('manager.inbox.send'));
        $this->assertSame('whatsapp_transactional', $map->capabilityFor('admin.whatsapp.mappings.update'));
        $this->assertSame('whatsapp_marketing', $map->capabilityFor('admin.whatsapp.campaigns.send_now'));
        $this->assertSame('ai_recommendations', $map->capabilityFor('manager.inbox.suggest-reply'));
        $this->assertSame('jobs', $map->capabilityFor('manager.bookings.convert-to-job'));
    }

    public function test_outbound_policy_keeps_manual_transactional_marketing_and_autonomy_separate(): void
    {
        $free = $this->companyOn(Plans::FREE, 'Free Outbound Garage');
        $service = $this->companyOn(Plans::SERVICE, 'Service Outbound Garage');
        $policy = app(OutboundEntitlementPolicy::class);

        $policy->assertAllowed($free, OutboundEntitlementPolicy::MANUAL);
        $policy->assertAllowed($service, OutboundEntitlementPolicy::TRANSACTIONAL);

        foreach ([OutboundEntitlementPolicy::MARKETING, OutboundEntitlementPolicy::AI_AUTONOMOUS] as $purpose) {
            try {
                $policy->assertAllowed($service, $purpose);
                $this->fail("Service unexpectedly received {$purpose} outbound capability.");
            } catch (\Illuminate\Auth\Access\AuthorizationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    private function companyOn(string $plan, string $name): Company
    {
        $company = Company::query()->create(['name' => $name, 'status' => 'active']);
        app(SubscriptionManager::class)->assignPlan($company, $plan);

        return $company->fresh();
    }

    private function tenantUser(Company $company, string $email): User
    {
        return User::query()->create([
            'company_id' => $company->id,
            'name' => 'Tenant Admin',
            'email' => $email,
            'password' => 'Strong-Password-2026!',
            'role' => 'admin',
            'status' => true,
            'must_change_password' => false,
        ]);
    }
}
