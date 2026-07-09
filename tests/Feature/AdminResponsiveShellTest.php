<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminResponsiveShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_pages_render_full_width_top_nav_shell(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('sf-admin-sidebar', false)
            ->assertDontSee('Admin Console')
            ->assertDontSee('aria-label="Admin navigation"', false)
            ->assertDontSee('lg:pl-72', false)
            ->assertSee('data-sf-shell-breakpoint="lg"', false)
            ->assertSee('max-w-none', false)
            ->assertSee('lg:flex', false)
            ->assertSee('lg:hidden', false);
    }

    public function test_manager_pages_do_not_render_admin_full_width_shell(): void
    {
        $manager = $this->user('manager');

        $this->actingAs($manager)
            ->get(route('manager.dashboard'))
            ->assertOk()
            ->assertDontSee('sf-admin-sidebar', false)
            ->assertDontSee('Admin Console')
            ->assertDontSee('lg:pl-72', false);
    }

    public function test_manager_inbox_uses_desktop_shell_at_lg_breakpoint(): void
    {
        $manager = $this->user('manager');

        $this->actingAs($manager)
            ->get(route('manager.inbox.index'))
            ->assertOk()
            ->assertSee('Manager\/Inbox\/Index', false)
            ->assertSee('data-sf-shell-breakpoint="lg"', false);
    }

    public function test_admin_inbox_keeps_desktop_shell_at_lg_breakpoint(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)
            ->get(route('admin.inbox.index'))
            ->assertOk()
            ->assertSee('Admin\/Inbox\/Index', false)
            ->assertSee('data-sf-shell-breakpoint="lg"', false);
    }

    private function user(string $role): User
    {
        $this->prepareUserSchema();

        $companyId = (int) DB::table('companies')->insertGetId([
            'name' => ucfirst($role).' Shell Garage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::factory()->create([
            'role' => $role,
            'company_id' => $companyId,
            'status' => true,
            'must_change_password' => false,
        ]);
    }

    private function prepareUserSchema(): void
    {
        $this->ensureColumn('users', 'role', fn (Blueprint $table) => $table->string('role')->nullable());
        $this->ensureColumn('users', 'company_id', fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable());
        $this->ensureColumn('users', 'status', fn (Blueprint $table) => $table->boolean('status')->default(true));
        $this->ensureColumn('users', 'must_change_password', fn (Blueprint $table) => $table->boolean('must_change_password')->default(false));

        $this->prepareDashboardSchema();
    }

    private function prepareDashboardSchema(): void
    {
        if (! Schema::hasTable('company_settings')) {
            Schema::create('company_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('key');
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('name')->nullable();
                $table->boolean('is_archived')->default(false);
                $table->softDeletes();
                $table->timestamps();
            });
        }
        $this->ensureColumn('clients', 'deleted_at', fn (Blueprint $table) => $table->softDeletes());

        if (! Schema::hasTable('leads')) {
            Schema::create('leads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('status')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
        $this->ensureColumn('leads', 'deleted_at', fn (Blueprint $table) => $table->softDeletes());

        if (! Schema::hasTable('opportunities')) {
            Schema::create('opportunities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('stage')->nullable();
                $table->boolean('is_archived')->default(false);
                $table->softDeletes();
                $table->timestamps();
            });
        }
        $this->ensureColumn('opportunities', 'deleted_at', fn (Blueprint $table) => $table->softDeletes());

        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('name')->nullable();
                $table->string('status')->nullable();
                $table->date('booking_date')->nullable();
                $table->string('slot')->nullable();
                $table->integer('expected_duration')->nullable();
                $table->dateTime('scheduled_at')->nullable();
                $table->dateTime('scheduled_end_at')->nullable();
                $table->string('service_type')->nullable();
                $table->boolean('is_archived')->default(false);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('status')->nullable();
                $table->softDeletes();
                $table->timestamps();
            });
        }
        $this->ensureColumn('jobs', 'deleted_at', fn (Blueprint $table) => $table->softDeletes());

        if (! Schema::hasTable('invoices')) {
            Schema::create('invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('status')->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->softDeletes();
                $table->timestamps();
            });
        }
        $this->ensureColumn('invoices', 'deleted_at', fn (Blueprint $table) => $table->softDeletes());

        if (! Schema::hasTable('communication_logs')) {
            Schema::create('communication_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->boolean('follow_up_required')->default(false);
                $table->dateTime('communication_date')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('message_logs')) {
            Schema::create('message_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('channel')->nullable();
                $table->string('direction')->nullable();
                $table->string('provider_status')->nullable();
                $table->string('to_number')->nullable();
                $table->string('from_number')->nullable();
                $table->string('template')->nullable();
                $table->boolean('is_ai')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid')->nullable();
                $table->text('connection')->nullable();
                $table->text('queue')->nullable();
                $table->longText('payload')->nullable();
                $table->longText('exception')->nullable();
                $table->timestamp('failed_at')->nullable();
            });
        }
    }

    private function ensureColumn(string $table, string $column, callable $definition): void
    {
        if (Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }
}
