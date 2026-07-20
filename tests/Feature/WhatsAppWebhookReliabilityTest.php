<?php

namespace Tests\Feature;

use App\Jobs\ProcessInboundWhatsApp;
use App\Models\MessageLog;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppWebhookReliabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareWebhookSchema();
        config([
            'services.meta.app_secret' => 'test-meta-app-secret',
            'services.meta_leads.app_secret' => 'test-meta-app-secret',
        ]);
    }

    public function test_meta_whatsapp_webhook_verification_uses_company_verify_token(): void
    {
        $this->company([
            'meta_verify_token' => 'test-verify-token',
            'meta_phone_number_id' => 'test-phone-number-id',
            'is_whatsapp_active' => true,
        ]);

        $this->get(route('api.webhooks.meta.whatsapp.verify', [
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'test-verify-token',
            'hub.challenge' => 'challenge-ok',
        ]))
            ->assertOk()
            ->assertSee('challenge-ok');

        $this->get(route('api.webhooks.meta.whatsapp.verify', [
            'hub.mode' => 'subscribe',
            'hub.verify_token' => 'wrong-token',
            'hub.challenge' => 'challenge-no',
        ]))
            ->assertForbidden();
    }

    public function test_meta_whatsapp_invalid_payload_is_safely_ignored_without_dispatch(): void
    {
        Queue::fake();

        $response = $this->signedMetaPost([
            'object' => 'whatsapp_business_account',
        ]);

        $response->assertNoContent();
        Queue::assertNotPushed(ProcessInboundWhatsApp::class);
    }

    public function test_meta_whatsapp_inbound_payload_dispatches_inbound_job(): void
    {
        Queue::fake();

        $companyId = $this->company([
            'meta_phone_number_id' => 'test-phone-number-id',
            'is_whatsapp_active' => true,
        ]);

        $response = $this->signedMetaPost([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => [
                            'phone_number_id' => 'test-phone-number-id',
                            'display_phone_number' => '971400000000',
                        ],
                        'contacts' => [[
                            'profile' => ['name' => 'Test Customer'],
                            'wa_id' => '971500000001',
                        ]],
                        'messages' => [[
                            'from' => '971500000001',
                            'id' => 'wamid.inbound.test',
                            'timestamp' => '1780000000',
                            'type' => 'text',
                            'text' => ['body' => 'Need service'],
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ]);

        $response->assertNoContent();

        Queue::assertPushed(ProcessInboundWhatsApp::class, function (ProcessInboundWhatsApp $job) use ($companyId) {
            return $job->companyId === $companyId
                && $job->connection === 'database'
                && $job->queue === 'default'
                && $job->provider === 'meta'
                && $job->from === '971500000001'
                && $job->to === '971400000000'
                && $job->body === 'Need service'
                && $job->sid === 'wamid.inbound.test';
        });
    }

    public function test_meta_whatsapp_inbound_payload_is_written_to_database_default_queue(): void
    {
        $companyId = $this->company([
            'meta_phone_number_id' => 'test-phone-number-id',
            'is_whatsapp_active' => true,
        ]);

        $this->signedMetaPost([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => [
                            'phone_number_id' => 'test-phone-number-id',
                            'display_phone_number' => '971400000000',
                        ],
                        'contacts' => [[
                            'profile' => ['name' => 'Queue Customer'],
                            'wa_id' => '971500000002',
                        ]],
                        'messages' => [[
                            'from' => '971500000002',
                            'id' => 'wamid.queue.test',
                            'timestamp' => '1780000000',
                            'type' => 'text',
                            'text' => ['body' => 'Hi'],
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ])->assertNoContent();

        $this->assertDatabaseHas('queue_jobs', [
            'queue' => 'default',
        ]);

        $payload = DB::table('queue_jobs')->where('queue', 'default')->value('payload');

        $this->assertStringContainsString(str_replace('\\', '\\\\', ProcessInboundWhatsApp::class), (string) $payload);
        $this->assertStringContainsString((string) $companyId, (string) $payload);
    }

    public function test_inbound_hi_runs_with_nlp_failure_and_sends_deterministic_menu(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => ['message' => 'Unauthorized']], 401),
        ]);

        config([
            'services.openai.api_key' => 'invalid-test-key',
            'services.openai.base_url' => 'https://api.openai.com/v1',
        ]);

        $sent = [];
        app()->instance(WhatsAppService::class, new class($sent) {
            public $sent;

            public function __construct(array &$sent)
            {
                $this->sent =& $sent;
            }

            public function sendText(string $toE164, string $body, array $context = []): array
            {
                $this->sent[] = compact('toE164', 'body', 'context');

                return ['id' => 'wamid.outbound.menu'];
            }
        });

        $companyId = $this->company([
            'meta_phone_number_id' => 'test-phone-number-id',
            'meta_access_token' => 'test-token',
            'is_whatsapp_active' => true,
        ]);

        $this->lead($companyId, '971586934377');

        (new ProcessInboundWhatsApp(
            from: '971586934377',
            to: '971400000000',
            body: 'Hi',
            sid: 'wamid.inbound.hi.menu',
            profileName: 'Sam Abhishek',
            provider: 'meta',
            payload: ['test' => true],
            companyId: $companyId
        ))->handle();

        $this->assertCount(1, $sent);
        $this->assertSame('971586934377', $sent[0]['toE164']);
        $this->assertSame($companyId, $sent[0]['context']['company_id']);
        $this->assertStringContainsString('1. Service', $sent[0]['body']);
        $this->assertStringContainsString('2. General Enquiry', $sent[0]['body']);
        $this->assertStringContainsString('3. Speak to the manager', $sent[0]['body']);

        $this->assertDatabaseHas('message_logs', [
            'company_id' => $companyId,
            'direction' => 'in',
            'provider_message_id' => 'wamid.inbound.hi.menu',
        ]);

        $this->assertDatabaseHas('message_logs', [
            'company_id' => $companyId,
            'direction' => 'out',
            'provider_message_id' => 'wamid.outbound.menu',
            'provider_status' => 'sent',
        ]);
    }

    public function test_same_provider_sid_is_skipped_before_automation_runs(): void
    {
        $sent = [];
        app()->instance(WhatsAppService::class, new class($sent) {
            public $sent;

            public function __construct(array &$sent)
            {
                $this->sent =& $sent;
            }

            public function sendText(string $toE164, string $body, array $context = []): array
            {
                $this->sent[] = compact('toE164', 'body', 'context');

                return ['id' => 'should-not-send'];
            }
        });

        $companyId = $this->company();

        MessageLog::create([
            'company_id' => $companyId,
            'direction' => 'in',
            'channel' => 'whatsapp',
            'from_number' => '971586934377',
            'to_number' => '971400000000',
            'body' => 'Hi',
            'provider_message_id' => 'wamid.same.sid',
            'provider_status' => 'received',
        ]);

        (new ProcessInboundWhatsApp(
            from: '971586934377',
            to: '971400000000',
            body: 'Hi',
            sid: 'wamid.same.sid',
            profileName: 'Sam Abhishek',
            provider: 'meta',
            companyId: $companyId
        ))->handle();

        $this->assertCount(0, $sent);
        $this->assertSame(1, MessageLog::where('company_id', $companyId)
            ->where('provider_message_id', 'wamid.same.sid')
            ->count());
    }

    public function test_meta_whatsapp_status_update_marks_message_log_failed(): void
    {
        $companyId = $this->company([
            'meta_phone_number_id' => 'test-phone-number-id',
            'is_whatsapp_active' => true,
        ]);

        $log = MessageLog::create([
            'company_id' => $companyId,
            'direction' => 'out',
            'channel' => 'whatsapp',
            'to_number' => '971500000001',
            'body' => 'Template attempt',
            'provider_message_id' => 'wamid.status.test',
            'provider_status' => 'sent',
        ]);

        $response = $this->signedMetaPost([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'metadata' => [
                            'phone_number_id' => 'test-phone-number-id',
                        ],
                        'statuses' => [[
                            'id' => 'wamid.status.test',
                            'status' => 'failed',
                            'timestamp' => '1780000001',
                            'recipient_id' => '971500000001',
                            'errors' => [[
                                'code' => 131047,
                                'title' => 'Re-engagement message',
                                'message' => 'Test failure',
                                'error_data' => ['details' => 'Customer care window closed'],
                            ]],
                        ]],
                    ],
                    'field' => 'messages',
                ]],
            ]],
        ]);

        $response->assertNoContent();

        $log->refresh();

        $this->assertSame('failed', $log->provider_status);
        $this->assertSame(131047, $log->meta['wa_error_code']);
        $this->assertSame('Re-engagement message', $log->meta['wa_error_title']);
    }

    private function signedMetaPost(array $payload)
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'test-meta-app-secret');

        return $this->call('POST', route('api.webhooks.meta.whatsapp.handle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body);
    }

    private function company(array $overrides = []): int
    {
        return (int) DB::table('companies')->insertGetId(array_merge([
            'name' => 'Webhook Garage',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function prepareWebhookSchema(): void
    {
        $this->ensureColumn('companies', 'meta_verify_token', fn (Blueprint $table) => $table->string('meta_verify_token')->nullable());
        $this->ensureColumn('companies', 'meta_phone_number_id', fn (Blueprint $table) => $table->string('meta_phone_number_id')->nullable());
        $this->ensureColumn('companies', 'meta_access_token', fn (Blueprint $table) => $table->text('meta_access_token')->nullable());
        $this->ensureColumn('companies', 'is_whatsapp_active', fn (Blueprint $table) => $table->boolean('is_whatsapp_active')->default(false));

        if (! Schema::hasTable('leads')) {
            Schema::create('leads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('client_id')->nullable();
                $table->string('name')->nullable();
                $table->string('email')->nullable();
                $table->string('email_norm')->nullable();
                $table->string('phone')->nullable();
                $table->string('phone_norm')->nullable();
                $table->string('status')->nullable();
                $table->string('source')->nullable();
                $table->string('preferred_channel')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('score')->default(0);
                $table->string('conversation_state')->nullable();
                $table->json('conversation_data')->nullable();
                $table->timestamp('conversation_updated_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('message_logs')) {
            Schema::create('message_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('lead_id')->nullable();
                $table->unsignedBigInteger('conversation_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('direction');
                $table->string('source')->nullable();
                $table->string('channel')->default('whatsapp');
                $table->string('to_number')->nullable();
                $table->string('from_number')->nullable();
                $table->string('template')->nullable();
                $table->unsignedBigInteger('template_id')->nullable();
                $table->text('body')->nullable();
                $table->string('provider_message_id')->nullable();
                $table->string('provider_status')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureColumn('message_logs', 'provider_message_id', fn (Blueprint $table) => $table->string('provider_message_id')->nullable());
        $this->ensureColumn('message_logs', 'provider_status', fn (Blueprint $table) => $table->string('provider_status')->nullable());
        $this->ensureColumn('message_logs', 'conversation_id', fn (Blueprint $table) => $table->unsignedBigInteger('conversation_id')->nullable());
        $this->ensureColumn('message_logs', 'source', fn (Blueprint $table) => $table->string('source')->nullable());
        $this->ensureColumn('message_logs', 'is_ai', fn (Blueprint $table) => $table->boolean('is_ai')->default(false));
        $this->ensureColumn('message_logs', 'ai_analysis', fn (Blueprint $table) => $table->json('ai_analysis')->nullable());
        $this->ensureColumn('message_logs', 'meta', fn (Blueprint $table) => $table->json('meta')->nullable());
    }

    private function lead(int $companyId, string $phone)
    {
        return \App\Models\Client\Lead::withoutEvents(fn () => \App\Models\Client\Lead::create([
            'company_id' => $companyId,
            'name' => 'Sam Abhishek',
            'phone' => $phone,
            'phone_norm' => preg_replace('/\D+/', '', $phone),
            'source' => 'whatsapp',
            'status' => \App\Models\Client\Lead::STATUS_NEW,
            'preferred_channel' => 'whatsapp',
            'conversation_state' => 'idle',
            'conversation_data' => [],
        ]));
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
