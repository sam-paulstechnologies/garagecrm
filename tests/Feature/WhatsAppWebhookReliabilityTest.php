<?php

namespace Tests\Feature;

use App\Jobs\ProcessInboundWhatsApp;
use App\Models\MessageLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                && $job->provider === 'meta'
                && $job->from === '971500000001'
                && $job->to === '971400000000'
                && $job->body === 'Need service'
                && $job->sid === 'wamid.inbound.test';
        });
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
        $this->ensureColumn('companies', 'is_whatsapp_active', fn (Blueprint $table) => $table->boolean('is_whatsapp_active')->default(false));

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
        $this->ensureColumn('message_logs', 'meta', fn (Blueprint $table) => $table->json('meta')->nullable());
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
