<?php

namespace Tests\Feature;

use App\Commercial\Plans;
use App\Commercial\SubscriptionManager;
use App\Models\System\Company;
use App\Models\User;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialPlanBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(CommercialFoundationSeeder::class);
    }

    public function test_header_uses_the_canonical_active_subscription_for_every_launch_plan(): void
    {
        foreach ([
            Plans::FREE => 'FREE',
            Plans::SERVICE => 'SERVICE',
            Plans::GROWTH => 'GROWTH',
            Plans::PERFORMANCE => 'PERFORMANCE',
            Plans::AI_PRO => 'AI PRO',
        ] as $code => $label) {
            [$company, $admin] = $this->tenant('Badge '.$label);
            app(SubscriptionManager::class)->assignPlan($company, $code);

            $response = $this->actingAs($admin)->get(route('admin.profile.edit'));
            $response->assertOk()
                ->assertSee('data-commercial-plan-badge', false)
                ->assertSee('data-commercial-plan-code="'.$code.'"', false)
                ->assertSee('>'.$label.'</span>', false);
        }
    }

    public function test_switching_subscription_updates_the_header_without_a_stale_label(): void
    {
        [$company, $admin] = $this->tenant('Switching Badge Garage');
        $subscriptions = app(SubscriptionManager::class);
        $subscriptions->assignPlan($company, Plans::FREE);

        $this->actingAs($admin)->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('data-commercial-plan-code="free"', false)
            ->assertSee('>FREE</span>', false);

        $subscriptions->assignPlan($company->fresh(), Plans::AI_PRO);

        $this->actingAs($admin)->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('data-commercial-plan-code="ai_pro"', false)
            ->assertSee('>AI PRO</span>', false)
            ->assertDontSee('data-commercial-plan-code="free"', false);
    }

    public function test_grandfathered_subscription_uses_its_stable_canonical_plan_code(): void
    {
        [$company, $admin] = $this->tenant('Grandfathered Badge Garage');
        app(SubscriptionManager::class)->assignPlan($company, Plans::PERFORMANCE, 'legacy_grandfathered');

        $this->actingAs($admin)->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('data-commercial-plan-code="performance"', false)
            ->assertSee('>PERFORMANCE</span>', false);
    }

    private function tenant(string $name): array
    {
        $company = Company::query()->create(['name' => $name, 'status' => 'active']);
        $admin = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
            'status' => true,
            'must_change_password' => false,
        ]);

        return [$company, $admin];
    }
}
