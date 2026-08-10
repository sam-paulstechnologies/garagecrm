<?php

namespace Tests\Feature;

use App\Commercial\Plans;
use App\Commercial\ProductEventRecorder;
use App\Commercial\ProductEvents;
use App\Commercial\SubscriptionManager;
use App\Models\System\Company;
use App\Models\User;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class CommercialObservabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CommercialFoundationSeeder::class);
    }

    public function test_product_events_are_whitelisted_privacy_minimized_and_idempotent(): void
    {
        [$company, $admin] = $this->tenant();
        $recorder = app(ProductEventRecorder::class);
        $first = $recorder->record(
            ProductEvents::REGISTRATION_COMPLETED,
            $company,
            $admin,
            ['plan_code' => Plans::FREE],
            'registration-completed:'.$company->id,
        );
        $second = $recorder->record(
            ProductEvents::REGISTRATION_COMPLETED,
            $company,
            $admin,
            ['plan_code' => Plans::FREE],
            'registration-completed:'.$company->id,
        );

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('product_events', 1);
        $this->assertSame(['plan_code' => Plans::FREE], $first->properties);
        $this->assertSame(64, strlen($first->dedupe_key));

        try {
            $recorder->record('unknown_event', $company, $admin);
            $this->fail('Unknown product event was accepted.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Unknown product event type.', $exception->getMessage());
        }

        $this->expectException(InvalidArgumentException::class);
        $recorder->record(
            ProductEvents::FIRST_LEAD,
            $company,
            $admin,
            ['source' => 'customer@example.test'],
        );
    }

    public function test_commercial_dashboard_is_platform_only_and_uses_internal_aggregates(): void
    {
        [$company, $admin] = $this->tenant();
        app(ProductEventRecorder::class)->record(
            ProductEvents::PLAN_UPGRADED,
            $company,
            properties: ['from_plan' => Plans::FREE, 'to_plan' => Plans::SERVICE, 'provider' => 'fake'],
            dedupeKey: 'synthetic-free-service-upgrade',
        );
        $platform = User::query()->create([
            'name' => 'Platform Commercial Reviewer',
            'email' => 'commercial-reviewer@staging.sayaraforce.test',
            'password' => 'Synthetic-Only-Password-2026!',
            'role' => 'super_admin',
            'status' => true,
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)->get(route('super-admin.commercial.index'))->assertForbidden();
        $this->actingAs($platform)->get(route('super-admin.commercial.index'))
            ->assertOk()
            ->assertSee('Commercial health')
            ->assertSee('Free → Service')
            ->assertSee('Payment fees')
            ->assertSee('Never guessed without provider data');
    }

    public function test_optional_telemetry_does_not_block_primary_operations_before_its_table_exists(): void
    {
        Schema::drop('product_events');

        $this->assertNull(app(ProductEventRecorder::class)->recordSafely(ProductEvents::REGISTRATION_STARTED));
    }

    /** @return array{Company, User} */
    private function tenant(): array
    {
        $company = Company::query()->create([
            'name' => 'Observability Garage',
            'email' => 'observability@staging.sayaraforce.test',
            'status' => 'active',
        ]);
        app(SubscriptionManager::class)->assignPlan($company, Plans::FREE);
        $admin = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Observability Admin',
            'email' => 'observability-admin@staging.sayaraforce.test',
            'password' => 'Synthetic-Only-Password-2026!',
            'role' => 'admin',
            'status' => true,
            'must_change_password' => false,
        ]);

        return [$company->fresh(), $admin];
    }
}
