<?php

namespace Tests\Feature;

use App\Commercial\EntitlementService;
use App\Commercial\Jobs\RequireJobEntitlement;
use App\Commercial\Plans;
use App\Commercial\SubscriptionManager;
use App\Jobs\GenerateAiSuggestion;
use App\Models\Commercial\CompanyEntitlementOverride;
use App\Models\Commercial\EntitlementAuditLog;
use App\Models\Commercial\EntitlementUsage;
use App\Models\Commercial\Price;
use App\Models\MessageLog;
use App\Models\System\Company;
use App\Models\System\Plan;
use App\Models\User;
use App\Services\Ai\AiOutboundSender;
use Carbon\CarbonImmutable;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use LogicException;
use Tests\TestCase;

class CommercialFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CommercialFoundationSeeder::class);
    }

    public function test_canonical_matrix_is_fail_closed_and_uses_stable_plan_codes(): void
    {
        $entitlements = app(EntitlementService::class);
        $free = $this->companyOn(Plans::FREE, 'Free Matrix');
        $service = $this->companyOn(Plans::SERVICE, 'Service Matrix');
        $growth = $this->companyOn(Plans::GROWTH, 'Growth Matrix');
        $performance = $this->companyOn(Plans::PERFORMANCE, 'Performance Matrix');
        $aiPro = $this->companyOn(Plans::AI_PRO, 'AI Pro Matrix');

        $this->assertTrue($entitlements->can($free, 'clients'));
        $this->assertFalse($entitlements->can($free, 'jobs'));
        $this->assertFalse($entitlements->can($free, 'unknown.capability'));
        $this->assertFalse($entitlements->can($free, 'platform.garages.manage'));
        $this->assertSame(25, $entitlements->limit($free, 'limit.ai_monitored_customers'));

        $this->assertTrue($entitlements->can($service, 'transactional_booking_reminders'));
        $this->assertFalse($entitlements->can($service, 'campaign_intelligence'));
        $this->assertTrue($entitlements->can($growth, 'source_attribution'));
        $this->assertFalse($entitlements->can($growth, 'campaign_intelligence'));
        $this->assertTrue($entitlements->can($performance, 'campaign_intelligence'));
        $this->assertFalse($entitlements->can($performance, 'ai_action_execution'));
        $this->assertTrue($entitlements->can($aiPro, 'ai_action_execution'));
        $this->assertSame('approval_required', $entitlements->decide($aiPro, 'ai_action_execution')->mode);
        $this->assertFalse($entitlements->can($aiPro, 'whatsapp_ai_autonomous'));
    }

    public function test_overrides_are_company_scoped_expire_and_do_not_leak(): void
    {
        $companyA = $this->companyOn(Plans::FREE, 'Override A');
        $companyB = $this->companyOn(Plans::FREE, 'Override B');
        $approver = $this->platformApprover();

        CompanyEntitlementOverride::query()->create([
            'company_id' => $companyA->id, 'capability' => 'jobs', 'enabled' => true,
            'value_type' => 'boolean', 'reason' => 'Approved staging pilot',
            'approved_by' => $approver->id, 'expires_at' => now()->addHour(),
        ]);

        $this->assertTrue(app(EntitlementService::class)->can($companyA, 'jobs'));
        $this->assertFalse(app(EntitlementService::class)->can($companyB, 'jobs'));

        CompanyEntitlementOverride::query()->where('company_id', $companyA->id)->update(['expires_at' => now()->subSecond()]);
        $this->assertFalse(app(EntitlementService::class)->can($companyA, 'jobs'));
    }

    public function test_inactive_cancelled_and_explicit_grandfathered_states_are_deterministic(): void
    {
        $company = $this->companyOn(Plans::GROWTH, 'Lifecycle Garage');
        $service = app(EntitlementService::class);
        $this->assertTrue($service->can($company, 'jobs'));

        $company->update(['status' => 'inactive']);
        $this->assertFalse($service->can($company->fresh(), 'jobs'));

        $company->update(['status' => 'active']);
        $company->subscription()->update(['status' => 'cancelled']);
        $this->assertFalse($service->can($company->fresh(), 'jobs'));

        $company->subscription()->update([
            'status' => 'legacy_grandfathered', 'grandfathered' => true,
            'grandfathered_snapshot' => ['capabilities' => ['jobs' => true]],
        ]);
        $this->assertTrue($service->can($company->fresh(), 'jobs'));
        $this->assertFalse($service->can($company->fresh(), 'invoices'));
    }

    public function test_planless_legacy_plan_id_and_expired_trial_fail_closed(): void
    {
        $legacyOnly = Company::query()->create([
            'name' => 'Legacy Plan Id Only',
            'status' => 'active',
            'plan_id' => Plan::query()->where('code', Plans::FREE)->value('id'),
        ]);

        $service = app(EntitlementService::class);
        $this->assertSame('unsubscribed', $service->subscriptionState($legacyOnly));
        $this->assertFalse($service->can($legacyOnly, 'clients'));

        $trial = $this->companyOn(Plans::SERVICE, 'Trial Lifecycle');
        $trial->subscription()->update(['status' => 'trialing', 'trial_ends_at' => now()->subMinute()]);
        $this->assertFalse($service->can($trial->fresh(), 'bookings'));

        $trial->subscription()->update(['trial_ends_at' => now()->addDay()]);
        $this->assertTrue($service->can($trial->fresh(), 'bookings'));
    }

    public function test_allowance_remaining_and_audit_history_are_company_scoped(): void
    {
        $company = $this->companyOn(Plans::FREE, 'Metered Free');
        $subscription = $company->subscription()->firstOrFail();
        $periodStart = $subscription->current_period_start->copy()->startOfSecond();

        EntitlementUsage::query()->create([
            'company_id' => $company->id,
            'capability' => 'limit.ai_monitored_customers',
            'period_start' => $periodStart,
            'period_end' => $subscription->current_period_end,
            'used' => 24,
        ]);

        $service = app(EntitlementService::class);
        $this->assertSame(25, $service->limit($company, 'limit.ai_monitored_customers'));
        $this->assertSame(1, $service->remaining($company, 'limit.ai_monitored_customers'));

        EntitlementUsage::query()->where('company_id', $company->id)->update(['used' => 25]);
        $this->assertSame(0, $service->remaining($company, 'limit.ai_monitored_customers'));
        $this->assertFalse($service->can($company, 'limit.ai_monitored_customers'));

        app(SubscriptionManager::class)->assignPlan($company, Plans::SERVICE);
        $events = EntitlementAuditLog::query()->where('company_id', $company->id)->orderBy('id')->get();
        $this->assertSame(['subscription.assigned', 'subscription.changed'], $events->pluck('event')->all());
        $this->assertSame(Plans::SERVICE, data_get($events->last()?->context, 'plan_code'));
    }

    public function test_ui_directive_uses_the_same_company_entitlement_decision(): void
    {
        $free = $this->companyOn(Plans::FREE, 'UI Free');
        $freeUser = $this->tenantUser($free, 'ui-free@example.test');
        $this->actingAs($freeUser);
        $this->assertSame('', trim(Blade::render("@entitled('campaign_intelligence')\npaid\n@endentitled")));

        $performance = $this->companyOn(Plans::PERFORMANCE, 'UI Performance');
        $performanceUser = $this->tenantUser($performance, 'ui-performance@example.test');
        $this->actingAs($performanceUser);
        $this->assertSame('paid', trim(Blade::render("@entitled('campaign_intelligence')\npaid\n@endentitled")));
    }

    public function test_api_and_queued_job_recheck_the_same_entitlement(): void
    {
        $free = $this->companyOn(Plans::FREE, 'Direct API Free');
        $user = $this->tenantUser($free, 'direct-api-free@example.test');
        Sanctum::actingAs($user);

        $this->getJson(route('api.whatsapp.campaigns.index'))->assertForbidden();

        $message = MessageLog::query()->create([
            'company_id' => $free->id, 'direction' => 'in', 'channel' => 'whatsapp',
            'from_number' => '+971500000001', 'body' => 'Synthetic inbound',
        ]);
        $job = new GenerateAiSuggestion($message->id);
        $ran = false;
        (new RequireJobEntitlement)->handle($job, function () use (&$ran): void {
            $ran = true;
        });
        $this->assertFalse($ran);

        app(SubscriptionManager::class)->assignPlan($free, Plans::AI_PRO);
        (new RequireJobEntitlement)->handle($job, function () use (&$ran): void {
            $ran = true;
        });
        $this->assertTrue($ran);

        $ran = false;
        $free->subscription()->update(['status' => 'cancelled']);
        (new RequireJobEntitlement)->handle($job, function () use (&$ran): void {
            $ran = true;
        });
        $this->assertFalse($ran);
    }

    public function test_launch_and_standard_prices_are_explicit_effective_immutable_records(): void
    {
        $service = Price::query()->where('code', 'service:2026-launch-v1:aed-monthly')->firstOrFail();
        $this->assertSame('399.00', $service->list_amount);
        $this->assertSame('199.00', $service->promotional_amount);
        $this->assertSame(3, $service->promotion_duration_months);

        $started = CarbonImmutable::parse('2026-08-10');
        $this->assertSame('199.00', $service->amountAt($started->addMonths(2), $started));
        $this->assertSame('399.00', $service->amountAt($started->addMonths(3), $started));

        $subscription = $this->companyOn(Plans::SERVICE, 'Price Lock Garage')->subscription()->firstOrFail();
        $this->assertSame($service->id, $subscription->price_id);
        $this->assertTrue($subscription->promotion_started_at->isSameDay(now()));
        $this->assertTrue($subscription->promotion_ends_at->isSameDay(now()->addMonths(3)));

        $this->expectException(LogicException::class);
        $service->list_amount = 400;
        $service->save();
    }

    public function test_catalogue_reseed_refuses_historical_price_drift(): void
    {
        DB::table('prices')
            ->where('code', 'service:2026-launch-v1:aed-monthly')
            ->update(['list_amount' => 400]);

        $this->expectException(LogicException::class);
        $this->seed(CommercialFoundationSeeder::class);
    }

    public function test_ai_pro_does_not_imply_autonomous_whatsapp_outbound(): void
    {
        $company = $this->companyOn(Plans::AI_PRO, 'AI Outbound Guard');
        $inbound = MessageLog::query()->create([
            'company_id' => $company->id,
            'direction' => 'in',
            'channel' => 'whatsapp',
            'from_number' => '+971500000099',
            'body' => 'Synthetic inbound only',
        ]);

        $this->expectException(AuthorizationException::class);
        AiOutboundSender::sendFromInbound($inbound, 'This must not be sent.');
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
            'company_id' => $company->id, 'name' => 'Tenant Admin', 'email' => $email,
            'password' => 'Secure-Password-2026!', 'role' => 'admin', 'status' => true,
            'must_change_password' => false,
        ]);
    }

    private function platformApprover(): User
    {
        return User::query()->create([
            'name' => 'Platform Approver', 'email' => 'platform-approver@example.test',
            'password' => 'Secure-Password-2026!', 'role' => 'super_admin', 'status' => true,
            'must_change_password' => false,
        ]);
    }
}
