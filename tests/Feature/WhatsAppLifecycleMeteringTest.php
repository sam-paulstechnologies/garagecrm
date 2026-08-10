<?php

namespace Tests\Feature;

use App\Commercial\Plans;
use App\Commercial\ProductEvents;
use App\Commercial\SubscriptionManager;
use App\Jobs\ProcessInboundWhatsApp;
use App\Messaging\Data\NormalizedIncomingMessage;
use App\Models\Client\Opportunity;
use App\Models\Commercial\AiCustomerUsage;
use App\Models\Commercial\EntitlementUsage;
use App\Models\MessageLog;
use App\Models\System\Company;
use App\SayaraForce\Messaging\SayaraForceMessagingAdapter;
use App\Services\Ai\AiMonitoringMeter;
use App\Services\Ai\NlpService;
use App\Services\Leads\LeadResolver;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppLifecycleMeteringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepareSqliteMessagingFixture();
        $this->seed(CommercialFoundationSeeder::class);
        config(['commercial.ai_metering.hmac_key' => 'phase-2-test-only-hmac-key']);
    }

    public function test_adapter_persists_raw_message_before_queue_dispatch_and_deduplicates(): void
    {
        Queue::fake();
        $company = $this->companyOn(Plans::FREE, 'Raw Capture Garage');
        $incoming = new NormalizedIncomingMessage(
            companyId: $company->id,
            connectionId: 0,
            productKey: 'sayaraforce',
            provider: 'meta_whatsapp',
            providerMessageId: 'wamid.phase2.raw',
            from: '971500000201',
            to: '971400000000',
            type: 'text',
            body: 'Hi',
        );

        app(SayaraForceMessagingAdapter::class)->handleIncoming($incoming);
        app(SayaraForceMessagingAdapter::class)->handleIncoming($incoming);

        $raw = MessageLog::query()->where('provider_message_id', 'wamid.phase2.raw')->firstOrFail();
        $this->assertSame('raw_captured', data_get($raw->meta, 'lifecycle_stage'));
        $this->assertNull($raw->lead_id);
        $this->assertSame(1, MessageLog::query()->where('provider_message_id', 'wamid.phase2.raw')->count());
        $this->assertDatabaseHas('product_events', [
            'company_id' => $company->id,
            'event_type' => ProductEvents::FIRST_INBOUND,
        ]);
        $this->assertSame(1, DB::table('product_events')->where('event_type', ProductEvents::FIRST_INBOUND)->count());
        Queue::assertPushed(ProcessInboundWhatsApp::class, 1);
        Queue::assertPushed(ProcessInboundWhatsApp::class, fn (ProcessInboundWhatsApp $job) => $job->messageLogId === $raw->id);
    }

    public function test_greeting_creates_basic_lead_without_opportunity_and_free_plan_sends_nothing(): void
    {
        $company = $this->companyOn(Plans::FREE, 'Clean Lifecycle Garage');
        $this->fakeNlp();

        (new ProcessInboundWhatsApp(
            from: '971500000202',
            to: '971400000000',
            body: 'Hi',
            sid: 'wamid.phase2.hi',
            provider: 'meta',
            companyId: $company->id,
        ))->handle();

        $lead = DB::table('leads')->where('company_id', $company->id)->first();
        $this->assertNotNull($lead);
        $this->assertSame('new', $lead->status);
        $this->assertSame(0, Opportunity::query()->where('company_id', $company->id)->count());
        $this->assertDatabaseHas('message_logs', [
            'company_id' => $company->id,
            'provider_message_id' => 'wamid.phase2.hi',
            'direction' => 'in',
        ]);
        $this->assertSame(0, MessageLog::query()->where('company_id', $company->id)->where('direction', 'out')->count());
        $this->assertDatabaseHas('entitlement_usages', [
            'company_id' => $company->id,
            'capability' => AiMonitoringMeter::ALLOWANCE,
            'used' => 1,
        ]);
        $this->assertDatabaseHas('product_events', [
            'company_id' => $company->id,
            'event_type' => ProductEvents::FIRST_LEAD,
        ]);
    }

    public function test_quota_exhaustion_preserves_crm_capture_and_skips_analysis(): void
    {
        $company = $this->companyOn(Plans::FREE, 'Quota Garage');
        $subscription = $company->subscription()->firstOrFail();
        EntitlementUsage::query()->create([
            'company_id' => $company->id,
            'capability' => AiMonitoringMeter::ALLOWANCE,
            'period_start' => $subscription->current_period_start->copy()->startOfSecond(),
            'period_end' => $subscription->current_period_end,
            'used' => 25,
        ]);

        $calls = $this->fakeNlp();
        (new ProcessInboundWhatsApp(
            from: '971500000203',
            to: '971400000000',
            body: 'Need service',
            sid: 'wamid.phase2.quota',
            provider: 'meta',
            companyId: $company->id,
        ))->handle();

        $this->assertSame(0, $calls->count);
        $this->assertDatabaseHas('message_logs', [
            'company_id' => $company->id,
            'provider_message_id' => 'wamid.phase2.quota',
        ]);
        $this->assertDatabaseHas('leads', ['company_id' => $company->id, 'status' => 'new']);
        $this->assertDatabaseHas('ai_analysis_runs', [
            'company_id' => $company->id,
            'status' => 'skipped_quota',
            'skipped_reason' => 'allowance_exhausted',
        ]);
    }

    public function test_repeated_and_cross_tenant_customers_are_metered_correctly_without_raw_identity(): void
    {
        $companyA = $this->companyOn(Plans::SERVICE, 'Meter A');
        $companyB = $this->companyOn(Plans::SERVICE, 'Meter B');
        $meter = app(AiMonitoringMeter::class);

        $first = $this->message($companyA, 'wamid.meter.1');
        $second = $this->message($companyA, 'wamid.meter.2');
        $otherTenant = $this->message($companyB, 'wamid.meter.3');

        $decision1 = $meter->claim($companyA, null, '+971 50 000 0204', $first, 'meta|971400000000');
        $decision2 = $meter->claim($companyA, null, '971500000204', $second, 'meta|971400000000');
        $decision3 = $meter->claim($companyB, null, '971500000204', $otherTenant, 'meta|971400000000');

        $this->assertTrue($decision1->newCustomer);
        $this->assertFalse($decision2->newCustomer);
        $this->assertTrue($decision3->newCustomer);
        $this->assertSame(2, AiCustomerUsage::query()->count());
        $this->assertSame(1, EntitlementUsage::query()->where('company_id', $companyA->id)->value('used'));
        $this->assertSame(1, EntitlementUsage::query()->where('company_id', $companyB->id)->value('used'));

        foreach (AiCustomerUsage::query()->get() as $usage) {
            $this->assertSame(64, strlen($usage->external_identity_hash));
            $this->assertStringNotContainsString('971500000204', $usage->external_identity_hash);
        }
    }

    public function test_subscription_period_boundary_counts_customer_again_in_new_period(): void
    {
        $company = $this->companyOn(Plans::SERVICE, 'Period Garage');
        $meter = app(AiMonitoringMeter::class);
        $first = $this->message($company, 'wamid.period.1');
        $meter->claim($company, null, '971500000205', $first, 'meta|971400000000');

        $subscription = $company->subscription()->firstOrFail();
        $subscription->forceFill([
            'current_period_start' => $subscription->current_period_start->copy()->addMonth(),
            'current_period_end' => $subscription->current_period_end->copy()->addMonth(),
        ])->save();

        $second = $this->message($company, 'wamid.period.2');
        $decision = $meter->claim($company->fresh(), null, '971500000205', $second, 'meta|971400000000');

        $this->assertTrue($decision->newCustomer);
        $this->assertSame(2, AiCustomerUsage::query()->where('company_id', $company->id)->count());
    }

    public function test_ai_usage_threshold_events_are_period_scoped_and_idempotent(): void
    {
        $company = $this->companyOn(Plans::FREE, 'Threshold Garage');
        $subscription = $company->subscription()->firstOrFail();
        $periodStart = $subscription->current_period_start->copy()->startOfSecond();
        EntitlementUsage::query()->create([
            'company_id' => $company->id,
            'capability' => AiMonitoringMeter::ALLOWANCE,
            'period_start' => $periodStart,
            'period_end' => $subscription->current_period_end,
            'used' => 12,
        ]);

        $meter = app(AiMonitoringMeter::class);
        $meter->claim($company, null, '971500000250', $this->message($company, 'wamid.threshold.50'), 'meta|threshold');
        EntitlementUsage::query()->where('company_id', $company->id)->update(['used' => 19]);
        $meter->claim($company, null, '971500000251', $this->message($company, 'wamid.threshold.80'), 'meta|threshold');
        EntitlementUsage::query()->where('company_id', $company->id)->update(['used' => 24]);
        $meter->claim($company, null, '971500000252', $this->message($company, 'wamid.threshold.100'), 'meta|threshold');

        foreach ([ProductEvents::AI_USAGE_50, ProductEvents::AI_USAGE_80, ProductEvents::AI_USAGE_100] as $event) {
            $this->assertSame(1, DB::table('product_events')
                ->where('company_id', $company->id)
                ->where('event_type', $event)
                ->count());
        }
    }

    public function test_raw_message_survives_lead_resolution_failure(): void
    {
        $company = $this->companyOn(Plans::FREE, 'Failure Garage');
        $this->mock(LeadResolver::class, function ($mock): void {
            $mock->shouldReceive('resolve')->once()->andReturnNull();
        });

        (new ProcessInboundWhatsApp(
            from: '971500000206',
            to: '971400000000',
            body: 'Synthetic failure path',
            sid: 'wamid.phase2.failure',
            provider: 'meta',
            companyId: $company->id,
        ))->handle();

        $raw = MessageLog::query()->where('provider_message_id', 'wamid.phase2.failure')->firstOrFail();
        $this->assertSame('lead_resolution_failed', data_get($raw->meta, 'lifecycle_stage'));
        $this->assertNull($raw->lead_id);
    }

    private function companyOn(string $plan, string $name): Company
    {
        $company = Company::query()->create(['name' => $name, 'status' => 'active']);
        app(SubscriptionManager::class)->assignPlan($company, $plan);

        return $company->fresh();
    }

    private function message(Company $company, string $providerId): MessageLog
    {
        return MessageLog::query()->create([
            'company_id' => $company->id,
            'direction' => 'in',
            'channel' => 'whatsapp',
            'source' => 'customer',
            'from_number' => '971500000204',
            'to_number' => '971400000000',
            'body' => 'Synthetic metering message',
            'provider_message_id' => $providerId,
            'provider_status' => 'received',
        ]);
    }

    private function fakeNlp(): object
    {
        $calls = new class
        {
            public int $count = 0;
        };

        app()->instance(NlpService::class, new class($calls) extends NlpService
        {
            public function __construct(private object $calls) {}

            public function analyzeWithTelemetry(string $text, array $context = []): array
            {
                $this->calls->count++;

                return [
                    'analysis' => [
                        'intent' => 'fallback', 'sentiment' => 'neutral', 'confidence' => 0,
                        'language' => 'en', 'entities' => [],
                    ],
                    'telemetry' => [
                        'provider' => 'fake', 'model' => 'phase2-fake', 'input_tokens' => 2,
                        'output_tokens' => 1, 'total_tokens' => 3, 'estimated_cost_micros' => 1,
                        'duration_ms' => 1,
                    ],
                ];
            }
        });

        return $calls;
    }

    /**
     * SQLite does not consume Laravel's MySQL schema dump. This fixture mirrors
     * only the canonical columns exercised by this isolated lifecycle test; the
     * MySQL clean-install gate validates the real baseline separately.
     */
    private function prepareSqliteMessagingFixture(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::dropIfExists('conversations');
            Schema::dropIfExists('opportunities');
            Schema::dropIfExists('leads');
            Schema::dropIfExists('clients');
        }

        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('name')->nullable();
                $table->string('phone')->nullable();
                $table->string('phone_norm')->nullable();
                $table->string('whatsapp')->nullable();
                $table->string('email')->nullable();
                $table->string('email_norm')->nullable();
                $table->string('source')->nullable();
                $table->string('status')->nullable();
                $table->string('preferred_channel')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('leads')) {
            Schema::create('leads', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('email_norm')->nullable();
                $table->string('phone')->nullable();
                $table->string('phone_norm')->nullable();
                $table->string('status')->default('new');
                $table->string('source')->nullable();
                $table->string('preferred_channel')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_hot')->default(false);
                $table->unsignedInteger('score')->default(0);
                $table->string('conversation_state')->nullable();
                $table->json('conversation_data')->nullable();
                $table->timestamp('conversation_updated_at')->nullable();
                $table->string('external_source')->nullable();
                $table->string('external_id')->nullable();
                $table->string('external_form_id')->nullable();
                $table->json('external_payload')->nullable();
                $table->timestamp('external_received_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('opportunities')) {
            Schema::create('opportunities', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('client_id')->nullable();
                $table->unsignedBigInteger('lead_id')->nullable();
                $table->string('title')->nullable();
                $table->string('stage')->nullable();
                $table->string('source')->nullable();
                $table->string('ai_status')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('client_id')->nullable();
                $table->unsignedBigInteger('lead_id')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('customer_phone')->nullable();
                $table->string('subject')->nullable();
                $table->timestamp('latest_message_at')->nullable();
                $table->timestamp('last_message_at')->nullable();
                $table->string('last_message_preview')->nullable();
                $table->unsignedInteger('unread_count')->default(0);
                $table->boolean('is_whatsapp_linked')->default(false);
                $table->timestamps();
            });
        }
    }
}
