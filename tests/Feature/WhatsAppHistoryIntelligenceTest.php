<?php

namespace Tests\Feature;

use App\Commercial\CampaignQuotaService;
use App\Commercial\EntitlementService;
use App\Commercial\Plans;
use App\Commercial\SubscriptionManager;
use App\Jobs\ProcessInboundWhatsApp;
use App\Models\Client\Client;
use App\Models\System\Company;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppHistoryContactUsage;
use App\Services\WhatsApp\History\HistoryImporter;
use App\Services\WhatsApp\History\HistoryIngestion;
use App\Services\WhatsApp\History\HistoryIntelligence;
use App\Services\WhatsApp\History\HistoryQuota;
use App\Services\WhatsApp\History\HistoryReview;
use App\Services\WhatsApp\History\HistoryTrackingPolicy;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WhatsAppHistoryIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CommercialFoundationSeeder::class);
        config([
            'services.meta.app_secret' => 'history-test-app-secret',
            'services.meta_leads.app_secret' => 'history-test-app-secret',
            'services.openai.api_key' => null,
            'messaging.history.hmac_key' => 'history-test-hmac-key',
        ]);
    }

    public function test_exact_launch_plan_resource_limits_come_from_canonical_entitlements(): void
    {
        $expected = [
            Plans::FREE => [1, 1, 1, 25, 25, 0, 0, 0],
            Plans::SERVICE => [3, 1, 1, 300, 300, 1, 100, 0],
            Plans::GROWTH => [10, 2, 2, 1000, 1000, 5, 500, 5],
            Plans::PERFORMANCE => [20, 5, 3, 2500, 2500, 20, 2000, 20],
            Plans::AI_PRO => [null, null, null, null, null, null, null, null],
        ];
        $keys = [
            'limit.users', 'limit.locations', 'limit.whatsapp_numbers',
            'limit.ai_monitored_customers', 'limit.whatsapp_history_contacts',
            'limit.campaigns_per_period', 'limit.campaign_recipients', 'limit.active_workflows',
        ];

        foreach ($expected as $plan => $limits) {
            $company = $this->companyOn($plan, 'Limits '.str_replace('_', ' ', $plan));
            foreach ($keys as $index => $key) {
                $decision = app(EntitlementService::class)->decide($company, $key);
                $this->assertSame(
                    $limits[$index] !== 0,
                    $decision->allowed,
                    "{$plan} {$key} allowance-state mismatch."
                );
                $this->assertSame($limits[$index], $decision->limit, "{$plan} {$key} mismatch.");
            }
        }
    }

    public function test_history_sync_quarantines_contacts_and_is_idempotent_without_operational_records(): void
    {
        $company = $this->metaCompany(Plans::FREE, 'Quarantine Garage');
        $payload = $this->historyPayload('971500001001', [
            'I need a quote for an oil service.',
        ]);

        $first = app(HistoryIngestion::class)->ingest($company, $payload, true);
        $second = app(HistoryIngestion::class)->ingest($company, $payload, true);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('whatsapp_history_candidates', 1);
        $this->assertDatabaseCount('whatsapp_history_messages', 1);
        $this->assertDatabaseCount('clients', 0);
        $this->assertDatabaseCount('message_logs', 0);
        $this->assertDatabaseCount('conversations', 0);
        $this->assertOperationalRecordsEmpty();
        $this->assertSame('awaiting_review', $second->fresh()->status);
    }

    public function test_intelligence_classifies_customer_personal_colleague_and_unknown_with_bounded_reasons(): void
    {
        $company = $this->metaCompany(Plans::PERFORMANCE, 'Classification Garage');
        $cases = [
            '971500001011' => ['Need a quote for my Toyota oil service', 'likely_customer'],
            '971500001012' => ['Family dinner this weekend, love you', 'possible_personal'],
            '971500001013' => ['Move it to bay 2 for the mechanic shift', 'possible_colleague'],
            '971500001014' => ['Hello there', 'unknown'],
        ];

        foreach ($cases as $phone => [$message, $expected]) {
            $batch = app(HistoryIngestion::class)->ingest($company, $this->historyPayload($phone, [$message]), true);
            $candidate = $batch->candidates()->where('phone_e164', '!=', null)->latest('id')->firstOrFail();
            $analysed = app(HistoryIntelligence::class)->analyse($candidate);
            $this->assertSame($expected, $analysed->classification);
            $this->assertLessThanOrEqual(180, mb_strlen((string) $analysed->classification_reason));
        }

        $this->assertDatabaseCount('whatsapp_history_contact_usages', 4);
    }

    public function test_free_history_allowance_is_cumulative_idempotent_and_upgrade_unlocks_more(): void
    {
        $company = $this->metaCompany(Plans::FREE, 'History Limit Garage');
        $quota = app(HistoryQuota::class);
        $batch = app(HistoryIngestion::class)->ingest(
            $company,
            $this->multiContactPayload(26),
            true,
        );
        $candidates = $batch->candidates()->orderBy('id')->get();

        foreach ($candidates as $candidate) {
            app(HistoryIntelligence::class)->analyse($candidate);
        }

        $this->assertSame(25, WhatsAppHistoryContactUsage::query()->where('company_id', $company->id)->count());
        $this->assertSame('locked', $candidates->last()->fresh()->intelligence_status);
        $this->assertSame(0, $quota->summary($company->id)['remaining']);

        app(SubscriptionManager::class)->assignPlan($company, Plans::SERVICE);
        app(HistoryIntelligence::class)->analyse($candidates->last());
        $this->assertSame(26, WhatsAppHistoryContactUsage::query()->where('company_id', $company->id)->count());
        $this->assertSame('analysed', $candidates->last()->fresh()->intelligence_status);

        app(HistoryIntelligence::class)->analyse($candidates->first());
        $this->assertSame(26, WhatsAppHistoryContactUsage::query()->where('company_id', $company->id)->count());
    }

    public function test_track_import_matches_same_tenant_client_and_never_creates_sales_or_automation_records(): void
    {
        $company = $this->metaCompany(Plans::GROWTH, 'Track Garage');
        $admin = $this->admin($company, 'track-admin@example.test');
        $batch = app(HistoryIngestion::class)->ingest(
            $company,
            $this->historyPayload('971500001021', ['Need a quote for my Toyota service']),
            true,
        );
        $candidate = $batch->candidates()->firstOrFail();
        app(HistoryIntelligence::class)->analyse($candidate);
        $existing = $this->legacyCompatibleClient($company, '+971500001021');
        app(HistoryReview::class)->decide($candidate, $admin, 'track');

        $imported = app(HistoryImporter::class)->import($candidate);
        app(HistoryImporter::class)->import($candidate);

        $this->assertSame($existing->id, $imported->imported_client_id);
        $this->assertSame('matched_client', $imported->import_status);
        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('message_logs', 1);
        $this->assertDatabaseHas('message_logs', [
            'company_id' => $company->id,
            'is_historical' => true,
            'source' => 'whatsapp_coexistence_history',
        ]);
        $this->assertOperationalRecordsEmpty();
    }

    public function test_dont_track_purges_staged_content_suppresses_future_crm_and_can_be_reversed_safely(): void
    {
        $company = $this->metaCompany(Plans::SERVICE, 'Privacy Garage');
        $admin = $this->admin($company, 'privacy-admin@example.test');
        $batch = app(HistoryIngestion::class)->ingest(
            $company,
            $this->historyPayload('971500001031', ['Family dinner this weekend']),
            true,
        );
        $candidate = $batch->candidates()->firstOrFail();
        app(HistoryIntelligence::class)->analyse($candidate);
        app(HistoryReview::class)->decide($candidate, $admin, 'dont_track');

        $policy = app(HistoryTrackingPolicy::class);
        $this->assertSame('dont_track', $policy->decision($company, '971500001031'));
        $this->assertNull($candidate->messages()->firstOrFail()->body);
        $this->assertNotNull($candidate->fresh()->staged_content_purged_at);
        $this->assertDatabaseCount('clients', 0);

        app(HistoryReview::class)->decide($candidate, $admin, 'track');
        $this->assertSame('track', $policy->decision($company, '971500001031'));
        $this->assertSame('requires_resync', $candidate->fresh()->import_status);
        $this->assertDatabaseCount('clients', 0);
    }

    public function test_pending_history_contact_future_message_is_quarantined_while_new_sender_uses_normal_flow(): void
    {
        Queue::fake();
        $company = $this->metaCompany(Plans::FREE, 'Future Policy Garage');
        app(HistoryIngestion::class)->ingest(
            $company,
            $this->historyPayload('971511111111', ['Older message']),
            true,
        );

        $this->signedMetaPost($this->inboundPayload('971511111111', 'wamid.pending.live'))->assertNoContent();
        Queue::assertNotPushed(ProcessInboundWhatsApp::class);
        $this->assertDatabaseCount('message_logs', 0);
        $this->assertDatabaseHas('whatsapp_webhook_events', ['status' => 'quarantined']);

        $this->signedMetaPost($this->inboundPayload('971522222222', 'wamid.new.live'))->assertNoContent();
        Queue::assertPushed(ProcessInboundWhatsApp::class, 1);
    }

    public function test_campaign_period_and_recipient_limits_are_plan_aware_and_fail_closed(): void
    {
        $company = $this->companyOn(Plans::SERVICE, 'Campaign Limit Garage');
        $quota = app(CampaignQuotaService::class);
        $quota->assertRecipients($company, 100);

        try {
            $quota->assertRecipients($company, 101);
            $this->fail('Service recipient limit did not fail closed.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        $quota->create($company, fn () => $company);
        $this->expectException(ValidationException::class);
        $quota->create($company, fn () => $this->companyOn(Plans::FREE, 'Second resource'));
    }

    public function test_staff_match_is_deterministic_and_does_not_consume_semantic_allowance(): void
    {
        $company = $this->metaCompany(Plans::FREE, 'Staff Match Garage');
        $staff = $this->admin($company, 'staff-match@example.test');
        $staff->forceFill(['phone' => '+971 50 000 1041'])->save();
        $batch = app(HistoryIngestion::class)->ingest(
            $company,
            $this->historyPayload('971500001041', ['Move it to bay 2']),
            true,
        );

        $candidate = $batch->candidates()->firstOrFail();
        $this->assertSame('deterministic', $candidate->intelligence_status);
        $this->assertSame('possible_colleague', $candidate->classification);
        $this->assertDatabaseCount('whatsapp_history_contact_usages', 0);
    }

    public function test_cross_tenant_history_decision_is_refused(): void
    {
        $companyA = $this->metaCompany(Plans::SERVICE, 'History Tenant A');
        $companyB = $this->companyOn(Plans::SERVICE, 'History Tenant B');
        $otherAdmin = $this->admin($companyB, 'other-history-admin@example.test');
        $candidate = app(HistoryIngestion::class)->ingest(
            $companyA,
            $this->historyPayload('971500001051', ['Need brake service']),
            true,
        )->candidates()->firstOrFail();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(HistoryReview::class)->decide($candidate, $otherAdmin, 'dont_track');
    }

    public function test_incompatible_ai_history_payload_falls_back_closed(): void
    {
        config(['services.openai.api_key' => 'synthetic-test-key']);
        Http::fake([
            '*' => Http::response(['choices' => [['message' => ['content' => json_encode([
                'classification' => 'definitely_personal',
                'retention_level' => 'guaranteed_sale',
            ])]]]], 200),
        ]);

        $this->assertNull(app(\App\Services\Ai\NlpService::class)
            ->analyzeHistoryConversation('Synthetic conversation', 90));
    }

    private function companyOn(string $plan, string $name): Company
    {
        $company = Company::query()->create(['name' => $name, 'status' => 'active']);
        app(SubscriptionManager::class)->assignPlan($company, $plan);

        return $company->fresh();
    }

    private function metaCompany(string $plan, string $name): Company
    {
        $company = $this->companyOn($plan, $name);
        $company->forceFill([
            'meta_waba_id' => '100200300',
            'meta_phone_number_id' => '400500600',
            'meta_display_phone_number' => '+971 50 000 0000',
            'is_whatsapp_active' => true,
            'whatsapp_connection_mode' => 'business_app_onboarding',
            'whatsapp_coexistence_enabled' => true,
        ])->save();

        return $company->fresh();
    }

    private function admin(Company $company, string $email): User
    {
        return User::query()->create([
            'company_id' => $company->id,
            'name' => 'History Admin',
            'email' => $email,
            'password' => 'Strong-Password-2026!',
            'role' => 'admin',
            'status' => true,
            'must_change_password' => false,
        ]);
    }

    private function historyPayload(string $phone, array $messages): array
    {
        return [
            'metadata' => ['phone_number_id' => '400500600'],
            'status' => 'completed',
            'history' => [[
                'status' => 'completed',
                'threads' => [[
                    'wa_id' => $phone,
                    'profile' => ['name' => 'Synthetic Contact'],
                    'messages' => collect($messages)->values()->map(fn (string $body, int $index) => [
                        'id' => 'wamid.history.'.$phone.'.'.$index,
                        'type' => 'text',
                        'text' => ['body' => $body],
                        'timestamp' => (string) now()->subDays(90)->addMinutes($index)->timestamp,
                    ])->all(),
                ]],
            ]],
        ];
    }

    private function multiContactPayload(int $count): array
    {
        $threads = [];
        for ($index = 1; $index <= $count; $index++) {
            $phone = '97155'.str_pad((string) $index, 7, '0', STR_PAD_LEFT);
            $threads[] = data_get($this->historyPayload($phone, ['Need an oil service quote']), 'history.0.threads.0');
        }

        return [
            'metadata' => ['phone_number_id' => '400500600'],
            'status' => 'completed',
            'history' => [['status' => 'completed', 'threads' => $threads]],
        ];
    }

    private function legacyCompatibleClient(Company $company, string $phone): Client
    {
        $data = [
            'company_id' => $company->id,
            'name' => 'Existing Synthetic Client',
            'phone' => $phone,
            'phone_norm' => Client::normalizePhone($phone),
            'email' => 'existing-history-client@example.test',
            'email_norm' => 'existing-history-client@example.test',
            'source' => 'synthetic_test',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('clients', 'vehicle')) {
            $data['vehicle'] = 'Synthetic Vehicle';
        }

        $id = DB::table('clients')->insertGetId($data);

        return Client::query()->findOrFail($id);
    }

    private function assertOperationalRecordsEmpty(): void
    {
        foreach (['leads', 'opportunities', 'bookings', 'jobs'] as $table) {
            if (Schema::hasTable($table)) {
                $this->assertSame(0, DB::table($table)->count(), "{$table} should remain empty.");
            }
        }
    }

    private function inboundPayload(string $from, string $messageId): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'id' => '100200300',
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'metadata' => ['phone_number_id' => '400500600', 'display_phone_number' => '971500000000'],
                        'contacts' => [['wa_id' => $from, 'profile' => ['name' => 'Synthetic Sender']]],
                        'messages' => [[
                            'from' => $from,
                            'id' => $messageId,
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => 'Hi'],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    private function signedMetaPost(array $payload)
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'history-test-app-secret');

        return $this->call('POST', route('api.webhooks.meta.whatsapp.handle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body);
    }
}
