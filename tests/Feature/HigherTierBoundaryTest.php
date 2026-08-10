<?php

namespace Tests\Feature;

use App\Commercial\CommercialActionGate;
use App\Commercial\EntitlementService;
use App\Commercial\Jobs\EntitlementAwareJob;
use App\Commercial\Jobs\ExplicitlyApprovedEntitlementJob;
use App\Commercial\Jobs\RequireJobEntitlement;
use App\Commercial\Plans;
use App\Commercial\RouteCapabilityMap;
use App\Commercial\SubscriptionManager;
use App\Jobs\GenerateAiSuggestion;
use App\Models\System\Company;
use App\Models\User;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HigherTierBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CommercialFoundationSeeder::class);
    }

    public function test_growth_includes_operations_and_management_but_not_performance_or_action_ai(): void
    {
        $company = $this->companyOn(Plans::GROWTH, 'Growth Boundary Garage');
        $entitlements = app(EntitlementService::class);

        foreach (['jobs', 'workshop', 'invoices', 'management_dashboard', 'staff_performance', 'source_attribution', 'retention_insights', 'campaign_attribution', 'ai_recommendations'] as $capability) {
            $this->assertTrue($entitlements->can($company, $capability), $capability);
        }

        foreach (['campaign_intelligence', 'marketing_roi', 'advanced_reports', 'ai_priority_scoring', 'ai_action_execution'] as $capability) {
            $this->assertFalse($entitlements->can($company, $capability), $capability);
        }
    }

    public function test_performance_adds_measurement_and_priority_intelligence_without_action_execution(): void
    {
        $company = $this->companyOn(Plans::PERFORMANCE, 'Performance Boundary Garage');
        $entitlements = app(EntitlementService::class);

        foreach (['campaign_intelligence', 'marketing_roi', 'advanced_reports', 'advanced_retention_insights', 'ai_priority_scoring'] as $capability) {
            $this->assertTrue($entitlements->can($company, $capability), $capability);
        }

        $this->assertFalse($entitlements->can($company, 'ai_action_execution'));
        $this->assertFalse($entitlements->can($company, 'whatsapp_ai_autonomous'));
    }

    public function test_ai_pro_actions_require_explicit_approval_and_never_imply_autonomous_whatsapp(): void
    {
        $company = $this->companyOn(Plans::AI_PRO, 'AI Pro Boundary Garage');
        $decision = app(EntitlementService::class)->decide($company, 'ai_action_execution');

        $this->assertTrue($decision->allowed);
        $this->assertSame('approval_required', $decision->mode);
        $this->assertFalse(app(EntitlementService::class)->can($company, 'whatsapp_ai_autonomous'));

        try {
            app(CommercialActionGate::class)->assertAllowed($company, 'ai_action_execution');
            $this->fail('Approval-required AI action was accepted without approval.');
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);
        }

        app(CommercialActionGate::class)->assertAllowed($company, 'ai_action_execution', explicitApproval: true);
        $this->addToAssertionCount(1);
    }

    public function test_background_middleware_blocks_unapproved_mode_and_accepts_explicit_approval(): void
    {
        $company = $this->companyOn(Plans::AI_PRO, 'AI Job Boundary Garage');
        $middleware = new RequireJobEntitlement();
        $called = false;

        $unapproved = new class($company->id) implements EntitlementAwareJob {
            public function __construct(private readonly int $companyId) {}
            public function entitlementCompanyId(): ?int { return $this->companyId; }
            public function entitlementCapability(): string { return 'ai_action_execution'; }
        };

        $middleware->handle($unapproved, function () use (&$called): void { $called = true; });
        $this->assertFalse($called);

        $approved = new class($company->id) implements EntitlementAwareJob, ExplicitlyApprovedEntitlementJob {
            public function __construct(private readonly int $companyId) {}
            public function entitlementCompanyId(): ?int { return $this->companyId; }
            public function entitlementCapability(): string { return 'ai_action_execution'; }
            public function commercialActionApproved(): bool { return true; }
        };

        $middleware->handle($approved, function () use (&$called): void { $called = true; });
        $this->assertTrue($called);
    }

    public function test_direct_higher_tier_routes_are_server_side_gated(): void
    {
        $service = $this->companyOn(Plans::SERVICE, 'Service Direct Route Garage');
        $growth = $this->companyOn(Plans::GROWTH, 'Growth Direct Route Garage');
        $performance = $this->companyOn(Plans::PERFORMANCE, 'Performance Direct Route Garage');

        $this->actingAs($this->admin($service, 'service-direct@example.test'))
            ->get(route('admin.marketing.campaigns.index'))->assertForbidden();
        $this->actingAs($this->admin($growth, 'growth-direct@example.test'))
            ->get(route('admin.reports.garage-summary'))->assertForbidden();
        $this->actingAs($this->admin($performance, 'performance-direct@example.test'))
            ->put(route('admin.ai.update'), [])->assertForbidden();
    }

    public function test_ai_routes_and_jobs_use_their_specific_tier_capabilities(): void
    {
        $map = app(RouteCapabilityMap::class);

        $this->assertSame('ai_observational', $map->capabilityFor('admin.ai.insights.index'));
        $this->assertSame('ai_recommendations', $map->capabilityFor('admin.ai.suggestions.index'));
        $this->assertSame('ai_action_execution', $map->capabilityFor('admin.ai.suggestions.approve'));
        $this->assertSame('ai_recommendations', (new GenerateAiSuggestion(123))->entitlementCapability());
    }

    public function test_no_tenant_plan_can_grant_platform_capabilities(): void
    {
        $entitlements = app(EntitlementService::class);

        foreach (Plans::codes() as $plan) {
            $company = $this->companyOn($plan, str($plan)->headline().' Platform Boundary Garage');
            $this->assertFalse($entitlements->can($company, 'platform.users.manage'));
        }
    }

    private function companyOn(string $plan, string $name): Company
    {
        $company = Company::query()->create(['name' => $name, 'status' => 'active']);
        app(SubscriptionManager::class)->assignPlan($company, $plan);

        return $company->fresh();
    }

    private function admin(Company $company, string $email): User
    {
        return User::query()->create([
            'company_id' => $company->id,
            'name' => 'Tier Admin',
            'email' => $email,
            'password' => 'Strong-Password-2026!',
            'role' => 'admin',
            'status' => true,
            'must_change_password' => false,
        ]);
    }
}
