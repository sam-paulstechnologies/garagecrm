<?php

namespace Tests\Feature;

use App\Commercial\SubscriptionManager;
use App\Models\Garage\Garage;
use App\Models\System\Company;
use App\Models\User;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRoleSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CommercialFoundationSeeder::class);
    }

    public function test_tenant_admin_cannot_create_or_promote_a_platform_user_even_with_a_crafted_request(): void
    {
        $this->assertFalse(defined(User::class.'::ROLES'));
        [$company, $admin, $garage] = $this->tenant('Role Guard A');

        $payload = [
            'name' => 'Attempted Platform User',
            'email' => 'attempted-platform@example.test',
            'phone' => null,
            'role' => 'super_admin',
            'password' => 'Secure-Password-2026!',
            'password_confirmation' => 'Secure-Password-2026!',
            'garage_id' => $garage->id,
            'status' => '1',
        ];

        $this->actingAs($admin)->post(route('admin.users.store'), $payload)->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'attempted-platform@example.test']);

        $target = $this->tenantUser($company, $garage, 'manager', 'promotion-target@example.test');
        $this->actingAs($admin)->patch(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'phone' => null,
            'role' => 'platform_admin',
            'garage_id' => $garage->id,
            'status' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertSame('manager', $target->fresh()->role);

        $this->actingAs($admin)->postJson(route('admin.users.store'), $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }

    public function test_manager_cannot_reach_tenant_or_platform_role_assignment_paths(): void
    {
        [$company, , $garage] = $this->tenant('Role Guard B');
        $manager = $this->tenantUser($company, $garage, 'manager', 'manager-role-guard@example.test');

        $this->actingAs($manager)->post(route('admin.users.store'), [])->assertForbidden();
        $this->actingAs($manager)->postJson(route('super-admin.platform-users.store'), [])->assertForbidden();
    }

    public function test_protected_platform_path_can_create_a_platform_user(): void
    {
        $superAdmin = User::query()->create([
            'name' => 'Platform Root', 'email' => 'platform-root@example.test',
            'password' => 'Secure-Password-2026!', 'role' => 'super_admin', 'status' => true,
            'must_change_password' => false, 'company_id' => null,
        ]);

        $this->actingAs($superAdmin)->postJson(route('super-admin.platform-users.store'), [
            'name' => 'Platform Operator',
            'email' => 'platform-operator@example.test',
            'role' => 'platform_admin',
            'password' => 'Secure-Password-2026!',
            'status' => true,
        ])->assertCreated()->assertJsonPath('role', 'platform_admin');

        $this->assertDatabaseHas('users', [
            'email' => 'platform-operator@example.test', 'role' => 'platform_admin', 'company_id' => null,
        ]);
    }

    public function test_suspended_company_is_blocked_after_authentication_while_platform_super_admin_remains_explicitly_allowed(): void
    {
        [$company, $admin] = $this->tenant('Suspended Garage');
        $company->update(['status' => 'suspended', 'suspended_at' => now()]);

        $this->actingAs($admin)->get('/dashboard')->assertForbidden();

        $superAdmin = User::query()->create([
            'name' => 'Platform Root Two', 'email' => 'platform-root-two@example.test',
            'password' => 'Secure-Password-2026!', 'role' => 'super_admin', 'status' => true,
            'must_change_password' => false, 'company_id' => null,
        ]);
        $this->actingAs($superAdmin)->get(route('super-admin.dashboard'))->assertOk();

        $company->update(['status' => 'inactive', 'suspended_at' => null]);
        $this->actingAs($admin)->get('/dashboard')->assertForbidden();
    }

    /** @return array{Company, User, Garage} */
    private function tenant(string $name): array
    {
        $company = Company::query()->create(['name' => $name, 'status' => 'active']);
        app(SubscriptionManager::class)->assignFree($company);
        $garage = Garage::query()->create(['company_id' => $company->id, 'name' => $name]);
        $admin = $this->tenantUser($company, $garage, 'admin', str($name)->slug().'@example.test');

        return [$company, $admin, $garage];
    }

    private function tenantUser(Company $company, Garage $garage, string $role, string $email): User
    {
        return User::query()->create([
            'company_id' => $company->id, 'garage_id' => $garage->id,
            'name' => str($role)->headline(), 'email' => $email,
            'password' => 'Secure-Password-2026!', 'role' => $role,
            'status' => true, 'must_change_password' => false,
        ]);
    }
}
