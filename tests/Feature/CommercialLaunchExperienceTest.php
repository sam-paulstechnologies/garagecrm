<?php

namespace Tests\Feature;

use App\Commercial\Plans;
use App\Commercial\SubscriptionManager;
use App\Commercial\UpsellPresentationService;
use App\Models\Commercial\EntitlementUsage;
use App\Models\MessageLog;
use App\Models\System\Company;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialLaunchExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CommercialFoundationSeeder::class);
    }

    public function test_contextual_free_upsell_uses_only_real_tenant_usage(): void
    {
        $company = Company::query()->create(['name' => 'Usage Garage', 'status' => 'active']);
        $subscription = app(SubscriptionManager::class)->assignPlan($company, Plans::FREE);
        MessageLog::query()->create([
            'company_id' => $company->id,
            'direction' => 'in',
            'channel' => 'whatsapp',
            'body' => 'Synthetic first enquiry',
        ]);
        MessageLog::query()->create([
            'company_id' => $company->id,
            'direction' => 'in',
            'channel' => 'whatsapp',
            'body' => 'Synthetic second enquiry',
        ]);
        EntitlementUsage::query()->create([
            'company_id' => $company->id,
            'capability' => 'limit.ai_monitored_customers',
            'period_start' => $subscription->current_period_start->copy()->startOfSecond(),
            'period_end' => $subscription->current_period_end,
            'used' => 25,
        ]);

        $upsell = app(UpsellPresentationService::class)->for($company->fresh(), 'jobs');

        $this->assertSame(Plans::SERVICE, $upsell['next_plan']);
        $this->assertSame(
            'You received 2 enquiries. SayaraForce monitored 25 of 25 included customers this billing period.',
            $upsell['usage_message'],
        );
        $this->assertSame('199.00', $upsell['launch_amount']);
        $this->assertSame('399.00', $upsell['standard_amount']);
    }

    public function test_contextual_upsell_is_absent_when_no_usage_exists(): void
    {
        $company = Company::query()->create(['name' => 'No Usage Garage', 'status' => 'active']);
        app(SubscriptionManager::class)->assignPlan($company, Plans::FREE);

        $upsell = app(UpsellPresentationService::class)->for($company->fresh(), 'jobs');

        $this->assertNull($upsell['usage_message']);
    }
}
