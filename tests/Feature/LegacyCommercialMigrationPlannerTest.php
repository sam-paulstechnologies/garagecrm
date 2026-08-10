<?php

namespace Tests\Feature;

use App\Commercial\LegacyCommercialMigrationPlanner;
use App\Commercial\Plans;
use App\Commercial\SubscriptionManager;
use App\Models\Commercial\Subscription;
use App\Models\System\Company;
use App\Models\System\Plan;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class LegacyCommercialMigrationPlannerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CommercialFoundationSeeder::class);
    }

    public function test_planner_classifies_synthetic_legacy_contracts_without_changing_any_assignment(): void
    {
        $legacy = Plan::query()->create([
            'name' => 'Synthetic Legacy Starter',
            'code' => 'legacy_synthetic_starter',
            'price' => 999,
            'currency' => 'AED',
            'features' => ['Jobs', 'Invoices', 'WhatsApp updates'],
            'status' => true,
        ]);
        Company::query()->create([
            'name' => 'Synthetic Legacy Contract',
            'email' => 'legacy-contract@staging.sayaraforce.test',
            'plan_id' => $legacy->id,
            'status' => 'active',
        ]);
        Company::query()->create([
            'name' => 'Synthetic Planless Contract',
            'email' => 'planless-contract@staging.sayaraforce.test',
            'status' => 'active',
        ]);
        $canonicalReference = Company::query()->create([
            'name' => 'Synthetic Canonical Reference',
            'email' => 'canonical-reference@staging.sayaraforce.test',
            'plan_id' => Plan::query()->where('code', Plans::SERVICE)->value('id'),
            'status' => 'active',
        ]);
        $canonical = Company::query()->create([
            'name' => 'Synthetic Explicit Canonical',
            'email' => 'explicit-canonical@staging.sayaraforce.test',
            'status' => 'active',
        ]);
        app(SubscriptionManager::class)->assignPlan($canonical, Plans::GROWTH);

        $before = [
            'subscriptions' => Subscription::query()->count(),
            'company_plans' => Company::query()->orderBy('id')->pluck('plan_id', 'id')->all(),
        ];
        $report = app(LegacyCommercialMigrationPlanner::class)->plan();

        $this->assertSame('read_only', $report['status']);
        $this->assertSame(4, $report['companies_scanned']);
        $this->assertSame(1, $report['classifications']['explicit_canonical']);
        $this->assertSame(1, $report['classifications']['legacy_contract_requires_snapshot']);
        $this->assertSame(1, $report['classifications']['canonical_plan_reference_requires_approval']);
        $this->assertSame(1, $report['classifications']['planless_requires_manual_review']);
        $this->assertSame(0, $report['automatic_changes']);
        $this->assertNull($report['company_ids_requiring_review']);
        $this->assertSame($before['subscriptions'], Subscription::query()->count());
        $this->assertSame($before['company_plans'], Company::query()->orderBy('id')->pluck('plan_id', 'id')->all());
        $this->assertDatabaseMissing('subscriptions', ['company_id' => $canonicalReference->id]);
    }

    public function test_command_output_is_aggregate_by_default_and_never_contains_tenant_contact_data(): void
    {
        Company::query()->create([
            'name' => 'Never Print This Garage',
            'email' => 'never-print-this@staging.sayaraforce.test',
            'status' => 'active',
        ]);

        Artisan::call('commercial:plan-legacy-migration', ['--json' => true]);
        $output = Artisan::output();

        $this->assertStringNotContainsString('Never Print This Garage', $output);
        $this->assertStringNotContainsString('never-print-this@staging.sayaraforce.test', $output);
        $this->assertStringContainsString('"automatic_changes":0', $output);
    }
}
