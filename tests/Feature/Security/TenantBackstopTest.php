<?php

namespace Tests\Feature\Security;

use App\Models\Client\Client;
use App\Models\System\Company;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantBackstopTest extends TestCase
{
    use RefreshDatabase;

    private function company(string $name): Company
    {
        return Company::query()->create(['name' => $name]);
    }

    private function clientFor(Company $company, string $name): Client
    {
        // Create without an active tenant context (unauthenticated) so company_id
        // is set explicitly, exercising the read-side scope independently.
        return Client::query()->create([
            'company_id' => $company->id,
            'name' => $name,
            'phone' => '+9715000000'.random_int(10, 99),
        ]);
    }

    public function test_tenant_user_only_sees_own_company_clients(): void
    {
        $a = $this->company('Garage A');
        $b = $this->company('Garage B');
        $clientA = $this->clientFor($a, 'A Customer');
        $clientB = $this->clientFor($b, 'B Customer');

        $userA = User::factory()->create(['company_id' => $a->id, 'role' => 'admin']);
        $this->actingAs($userA);

        $ids = Client::query()->pluck('id')->all();
        $this->assertContains($clientA->id, $ids);
        $this->assertNotContains($clientB->id, $ids, 'Backstop must hide other tenant clients.');

        // Direct id lookup of the other tenant returns nothing (invisible).
        $this->assertNull(Client::query()->find($clientB->id));
    }

    public function test_run_as_platform_bypasses_scope_explicitly(): void
    {
        $a = $this->company('Garage A');
        $b = $this->company('Garage B');
        $this->clientFor($a, 'A Customer');
        $this->clientFor($b, 'B Customer');

        $userA = User::factory()->create(['company_id' => $a->id, 'role' => 'admin']);
        $this->actingAs($userA);

        $all = app(TenantContext::class)->runAsPlatform(fn () => Client::query()->count());
        $this->assertSame(2, $all, 'Explicit platform bypass must see all tenants.');

        // Outside the bypass, scope is back in force.
        $this->assertSame(1, Client::query()->count());
    }

    public function test_for_tenant_scopes_to_the_named_company(): void
    {
        $a = $this->company('Garage A');
        $b = $this->company('Garage B');
        $this->clientFor($a, 'A Customer');
        $this->clientFor($b, 'B Customer');

        // No authenticated user; explicit tenant context drives the scope.
        $countB = app(TenantContext::class)->forTenant($b->id, fn () => Client::query()->count());
        $this->assertSame(1, $countB);
    }
}
