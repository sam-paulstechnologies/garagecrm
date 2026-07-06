<?php

namespace Tests\Feature;

use App\Models\CompanyModuleSetting;
use App\Models\SuperAdminAuditLog;
use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SuperAdminControlCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
    }

    public function test_unauthenticated_user_is_redirected_from_super_admin(): void
    {
        $this->get(route('super-admin.dashboard'))
            ->assertRedirect('/login');
    }

    public function test_garage_admin_and_manager_cannot_access_super_admin(): void
    {
        $company = $this->company('Tenant Garage');

        $this->actingAs($this->user('admin', $company->id, 'admin-super-deny@example.test'))
            ->get(route('super-admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($this->user('manager', $company->id, 'manager-super-deny@example.test'))
            ->get(route('super-admin.dashboard'))
            ->assertForbidden();
    }

    public function test_super_admin_can_access_dashboard_and_view_all_garages(): void
    {
        $garageA = $this->company('Alpha Garage');
        $garageB = $this->company('Beta Garage');

        $this->actingAs($this->superAdmin())
            ->get(route('super-admin.dashboard'))
            ->assertOk()
            ->assertSee('SayaraForce Control Center');

        $this->actingAs($this->superAdmin())
            ->get(route('super-admin.garages.index'))
            ->assertOk()
            ->assertSee($garageA->name)
            ->assertSee($garageB->name);
    }

    public function test_message_and_lead_logs_render_cross_tenant_records(): void
    {
        $garageA = $this->company('Message Garage A');
        $garageB = $this->company('Message Garage B');

        DB::table('leads')->insert([
            [
                'company_id' => $garageA->id,
                'name' => 'Ali Lead',
                'phone' => '971500000001',
                'source' => 'website',
                'status' => 'new',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $garageB->id,
                'name' => 'Sara Lead',
                'phone' => '971500000002',
                'source' => 'whatsapp',
                'status' => 'qualified',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('message_logs')->insert([
            [
                'company_id' => $garageA->id,
                'direction' => 'in',
                'channel' => 'whatsapp',
                'from_number' => '971500000001',
                'body' => 'Need service',
                'provider_status' => 'received',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'company_id' => $garageB->id,
                'direction' => 'out',
                'channel' => 'whatsapp',
                'to_number' => '971500000002',
                'body' => 'Confirmed',
                'provider_status' => 'sent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($this->superAdmin())
            ->get(route('super-admin.logs.messages'))
            ->assertOk()
            ->assertSee($garageA->name)
            ->assertSee($garageB->name)
            ->assertSee('Need service')
            ->assertSee('Confirmed');

        $this->actingAs($this->superAdmin())
            ->get(route('super-admin.logs.leads'))
            ->assertOk()
            ->assertSee($garageA->name)
            ->assertSee($garageB->name)
            ->assertSee('Ali Lead')
            ->assertSee('Sara Lead');
    }

    public function test_module_toggle_and_garage_status_actions_create_audit_logs(): void
    {
        $garage = $this->company('Audit Garage');
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->patch(route('super-admin.garages.modules.update', $garage), [
                'module_key' => 'inbox',
                'enabled' => '0',
                'locked' => '1',
                'notes' => 'Paused for pilot review.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('company_module_settings', [
            'company_id' => $garage->id,
            'module_key' => 'inbox',
            'enabled' => false,
            'locked' => true,
        ]);

        $this->assertDatabaseHas('super_admin_audit_logs', [
            'company_id' => $garage->id,
            'action' => 'module.updated',
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.garages.suspend', $garage), ['reason' => 'Payment review'])
            ->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'id' => $garage->id,
            'status' => 'suspended',
        ]);

        $this->assertDatabaseHas('super_admin_audit_logs', [
            'company_id' => $garage->id,
            'action' => 'garage.suspended',
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.garages.activate', $garage))
            ->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'id' => $garage->id,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('super_admin_audit_logs', [
            'company_id' => $garage->id,
            'action' => 'garage.activated',
        ]);
    }

    public function test_channel_health_masks_sensitive_values(): void
    {
        $garage = $this->company('Secret Safe Garage');
        $garage->forceFill([
            'meta_phone_number_id' => '123456789012345',
            'meta_waba_id' => '987654321098765',
            'meta_access_token' => 'EAAB_SECRET_ACCESS_TOKEN',
            'meta_verify_token' => 'VERIFY_TOKEN_SECRET',
            'is_whatsapp_active' => true,
        ])->save();

        $this->actingAs($this->superAdmin())
            ->get(route('super-admin.garages.channels', $garage))
            ->assertOk()
            ->assertSee('1234...2345')
            ->assertSee('9876...8765')
            ->assertDontSee('EAAB_SECRET_ACCESS_TOKEN')
            ->assertDontSee('VERIFY_TOKEN_SECRET');
    }

    public function test_tenant_users_do_not_see_super_admin_navigation(): void
    {
        $company = $this->company('Nav Garage');

        $this->actingAs($this->user('admin', $company->id, 'admin-nav@example.test'))
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($this->user('manager', $company->id, 'manager-nav@example.test'))
            ->get('/dashboard')
            ->assertRedirect(route('manager.dashboard'));
    }

    private function company(string $name): Company
    {
        return Company::create([
            'name' => $name,
            'email' => str($name)->slug().'-owner@example.test',
            'phone' => '97150000'.random_int(1000, 9999),
            'status' => 'active',
        ]);
    }

    private function superAdmin(): User
    {
        return $this->user('super_admin', null, 'super-admin@example.test');
    }

    private function user(string $role, ?int $companyId, string $email): User
    {
        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => str($role)->headline().' User',
                'password' => 'password',
                'role' => $role,
                'company_id' => $companyId,
                'status' => true,
                'must_change_password' => false,
            ]
        );
    }

    private function prepareSchema(): void
    {
        $this->ensureColumn('companies', 'status', fn (Blueprint $table) => $table->string('status')->default('active'));
        $this->ensureColumn('companies', 'suspended_at', fn (Blueprint $table) => $table->timestamp('suspended_at')->nullable());
        $this->ensureColumn('companies', 'meta_phone_number_id', fn (Blueprint $table) => $table->string('meta_phone_number_id')->nullable());
        $this->ensureColumn('companies', 'meta_waba_id', fn (Blueprint $table) => $table->string('meta_waba_id')->nullable());
        $this->ensureColumn('companies', 'meta_access_token', fn (Blueprint $table) => $table->text('meta_access_token')->nullable());
        $this->ensureColumn('companies', 'meta_verify_token', fn (Blueprint $table) => $table->string('meta_verify_token')->nullable());
        $this->ensureColumn('companies', 'is_whatsapp_active', fn (Blueprint $table) => $table->boolean('is_whatsapp_active')->default(false));

        foreach ([
            'phone' => fn (Blueprint $table) => $table->string('phone')->nullable(),
            'role' => fn (Blueprint $table) => $table->string('role')->nullable(),
            'company_id' => fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable(),
            'garage_id' => fn (Blueprint $table) => $table->unsignedBigInteger('garage_id')->nullable(),
            'status' => fn (Blueprint $table) => $table->boolean('status')->default(true),
            'must_change_password' => fn (Blueprint $table) => $table->boolean('must_change_password')->default(false),
        ] as $column => $definition) {
            $this->ensureColumn('users', $column, $definition);
        }

        if (! Schema::hasTable('leads')) {
            Schema::create('leads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('name')->nullable();
                $table->string('phone')->nullable();
                $table->string('source')->nullable();
                $table->string('status')->nullable();
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->string('external_source')->nullable();
                $table->string('external_id')->nullable();
                $table->timestamps();
            });
        }

        foreach ([
            'company_id' => fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable(),
            'name' => fn (Blueprint $table) => $table->string('name')->nullable(),
            'phone' => fn (Blueprint $table) => $table->string('phone')->nullable(),
            'source' => fn (Blueprint $table) => $table->string('source')->nullable(),
            'status' => fn (Blueprint $table) => $table->string('status')->nullable(),
            'assigned_to' => fn (Blueprint $table) => $table->unsignedBigInteger('assigned_to')->nullable(),
            'external_source' => fn (Blueprint $table) => $table->string('external_source')->nullable(),
            'external_id' => fn (Blueprint $table) => $table->string('external_id')->nullable(),
        ] as $column => $definition) {
            $this->ensureColumn('leads', $column, $definition);
        }

        if (! Schema::hasTable('message_logs')) {
            Schema::create('message_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('lead_id')->nullable();
                $table->string('direction')->nullable();
                $table->string('channel')->nullable();
                $table->string('to_number')->nullable();
                $table->string('from_number')->nullable();
                $table->text('body')->nullable();
                $table->string('provider_status')->nullable();
                $table->timestamps();
            });
        }
    }

    private function ensureColumn(string $table, string $column, callable $definition): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, $definition);
    }
}
