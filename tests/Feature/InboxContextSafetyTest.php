<?php

namespace Tests\Feature;

use App\Models\Client\Lead;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery\MockInterface;
use Tests\TestCase;

class InboxContextSafetyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $manager;
    private int $companyId;
    private int $otherCompanyId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareInboxSchema();

        $this->companyId = (int) DB::table('companies')->insertGetId([
            'name' => 'Inbox Garage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->otherCompanyId = (int) DB::table('companies')->insertGetId([
            'name' => 'Other Inbox Garage',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->admin = User::create([
            'name' => 'Inbox Admin',
            'email' => 'inbox-admin@example.test',
            'password' => 'password',
            'role' => 'admin',
            'company_id' => $this->companyId,
            'status' => true,
            'must_change_password' => false,
        ]);

        $this->manager = User::create([
            'name' => 'Inbox Manager',
            'email' => 'inbox-manager@example.test',
            'password' => 'password',
            'role' => 'manager',
            'company_id' => $this->companyId,
            'status' => true,
            'must_change_password' => false,
        ]);
    }

    public function test_admin_lists_only_same_company_conversations(): void
    {
        $own = $this->conversation($this->companyId, ['customer_name' => 'Own Customer']);
        $other = $this->conversation($this->otherCompanyId, ['customer_name' => 'Other Customer']);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.inbox.list'))
            ->assertOk()
            ->json('conversations');

        $this->assertContains($own->id, collect($response)->pluck('id')->all());
        $this->assertNotContains($other->id, collect($response)->pluck('id')->all());
    }

    public function test_admin_cannot_read_or_send_cross_company_conversation(): void
    {
        $conversation = $this->conversation($this->otherCompanyId);

        $this->actingAs($this->admin)
            ->getJson(route('admin.inbox.messages', $conversation))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->postJson(route('admin.inbox.send'), [
                'conversation_id' => $conversation->id,
                'message' => 'Hello',
            ])
            ->assertNotFound();
    }

    public function test_manager_lists_only_same_company_conversations(): void
    {
        $own = $this->conversation($this->companyId, ['customer_name' => 'Manager Own']);
        $other = $this->conversation($this->otherCompanyId, ['customer_name' => 'Manager Other']);

        $response = $this->actingAs($this->manager)
            ->getJson(route('manager.inbox.list'))
            ->assertOk()
            ->json('conversations');

        $this->assertContains($own->id, collect($response)->pluck('id')->all());
        $this->assertNotContains($other->id, collect($response)->pluck('id')->all());
    }

    public function test_manager_cannot_read_or_send_cross_company_conversation(): void
    {
        $conversation = $this->conversation($this->otherCompanyId);

        $this->actingAs($this->manager)
            ->getJson(route('manager.inbox.messages', $conversation))
            ->assertForbidden();

        $this->actingAs($this->manager)
            ->postJson(route('manager.inbox.send'), [
                'conversation_id' => $conversation->id,
                'message' => 'Hello',
            ])
            ->assertNotFound();
    }

    public function test_admin_suggest_reply_route_returns_read_only_suggestion(): void
    {
        $conversation = $this->conversation($this->companyId);

        MessageLog::create([
            'company_id' => $this->companyId,
            'conversation_id' => $conversation->id,
            'direction' => 'in',
            'channel' => 'whatsapp',
            'from_number' => $conversation->customer_phone,
            'body' => 'How much will it cost?',
            'source' => 'human',
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.inbox.suggest-reply'), [
                'conversation_id' => $conversation->id,
                'tone' => 'short',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonStructure(['suggestion', 'tone']);
    }

    public function test_manual_admin_reply_sets_linked_lead_to_human_without_real_send(): void
    {
        $lead = $this->lead($this->companyId, [
            'phone' => '971500000123',
            'conversation_state' => 'bot',
        ]);

        $conversation = $this->conversation($this->companyId, [
            'lead_id' => $lead->id,
            'customer_phone' => '+971500000123',
        ]);

        $this->mockSuccessfulWhatsappSend($conversation->customer_phone, 'Manual reply');

        $this->actingAs($this->admin)
            ->postJson(route('admin.inbox.send'), [
                'conversation_id' => $conversation->id,
                'message' => 'Manual reply',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame('human', $lead->fresh()->conversation_state);
        $this->assertDatabaseHas('message_logs', [
            'conversation_id' => $conversation->id,
            'lead_id' => $lead->id,
            'direction' => 'out',
            'source' => 'human',
            'body' => 'Manual reply',
        ]);
    }

    public function test_manual_manager_reply_sets_phone_matched_lead_to_human_without_real_send(): void
    {
        $lead = $this->lead($this->companyId, [
            'phone' => '0500000124',
            'conversation_state' => 'bot',
        ]);

        $conversation = $this->conversation($this->companyId, [
            'lead_id' => null,
            'customer_phone' => '971500000124',
        ]);

        $this->mockSuccessfulWhatsappSend($conversation->customer_phone, 'Manager reply');

        $this->actingAs($this->manager)
            ->postJson(route('manager.inbox.send'), [
                'conversation_id' => $conversation->id,
                'message' => 'Manager reply',
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertSame('human', $lead->fresh()->conversation_state);
        $this->assertDatabaseHas('message_logs', [
            'conversation_id' => $conversation->id,
            'lead_id' => $lead->id,
            'direction' => 'out',
            'source' => 'human',
            'body' => 'Manager reply',
        ]);
    }

    public function test_provider_failure_returns_clear_json_error(): void
    {
        $conversation = $this->conversation($this->companyId);

        $this->mock(WhatsAppService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendText')
                ->once()
                ->andThrow(new \RuntimeException('Provider unavailable'));
        });

        $this->actingAs($this->admin)
            ->postJson(route('admin.inbox.send'), [
                'conversation_id' => $conversation->id,
                'message' => 'This will fail',
            ])
            ->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'WhatsApp message could not be sent. Please check WhatsApp settings and try again.');

        $this->assertDatabaseMissing('message_logs', [
            'conversation_id' => $conversation->id,
            'body' => 'This will fail',
        ]);
    }

    public function test_phone_search_uses_normalized_match_without_cross_company_leakage(): void
    {
        $clientId = (int) DB::table('clients')->insertGetId([
            'company_id' => $this->companyId,
            'name' => 'Phone Match Client',
            'phone' => '0500000222',
            'phone_norm' => '971500000222',
            'whatsapp' => '0500000222',
            'email' => 'phone-match@example.test',
            'email_norm' => 'phone-match@example.test',
            'vehicle' => 'Honda Accord',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $match = $this->conversation($this->companyId, [
            'client_id' => $clientId,
            'customer_name' => 'Phone Match',
            'customer_phone' => '+971 50 000 0222',
        ]);

        $this->conversation($this->otherCompanyId, [
            'customer_name' => 'Other Phone Match',
            'customer_phone' => '+971500000222',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('admin.inbox.list', ['search' => '0500000222']))
            ->assertOk()
            ->json('conversations');

        $ids = collect($response)->pluck('id')->all();

        $this->assertSame([$match->id], $ids);
    }

    private function mockSuccessfulWhatsappSend(string $expectedTo, string $expectedBody): void
    {
        $this->mock(WhatsAppService::class, function (MockInterface $mock) use ($expectedTo, $expectedBody) {
            $mock->shouldReceive('sendText')
                ->once()
                ->with($expectedTo, $expectedBody, ['company_id' => $this->companyId])
                ->andReturn(['ok' => true]);
        });
    }

    private function conversation(int $companyId, array $overrides = []): Conversation
    {
        return Conversation::create(array_merge([
            'company_id' => $companyId,
            'customer_name' => 'Inbox Customer',
            'customer_phone' => '+971500000001',
            'last_message_preview' => 'Latest inbox message',
            'last_message_at' => now(),
            'latest_message_at' => now(),
            'unread_count' => 0,
            'is_whatsapp_linked' => true,
        ], $overrides));
    }

    private function lead(int $companyId, array $overrides = []): Lead
    {
        $data = array_merge([
            'company_id' => $companyId,
            'name' => 'Inbox Lead',
            'phone' => '971500000001',
            'email' => 'inbox-lead@example.test',
            'status' => Lead::STATUS_NEW,
        ], $overrides);

        $data['phone_norm'] ??= Lead::normalizePhone($data['phone'] ?? null);
        $data['email_norm'] ??= Lead::normalizeEmail($data['email'] ?? null);

        return Lead::withoutEvents(fn () => Lead::create($data));
    }

    private function prepareInboxSchema(): void
    {
        $this->ensureColumn('users', 'role', fn (Blueprint $table) => $table->string('role')->nullable());
        $this->ensureColumn('users', 'company_id', fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable());
        $this->ensureColumn('users', 'status', fn (Blueprint $table) => $table->boolean('status')->default(true));
        $this->ensureColumn('users', 'must_change_password', fn (Blueprint $table) => $table->boolean('must_change_password')->default(false));

        if (! Schema::hasTable('clients')) {
            Schema::create('clients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('name');
                $table->string('phone')->nullable();
                $table->string('phone_norm')->nullable();
                $table->string('whatsapp')->nullable();
                $table->string('email')->nullable();
                $table->string('email_norm')->nullable();
                $table->timestamps();
            });
        }

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
                $table->string('conversation_state')->nullable();
                $table->json('conversation_data')->nullable();
                $table->timestamp('conversation_updated_at')->nullable();
                $table->timestamps();
            });
        }

        $this->ensureColumn('leads', 'phone_norm', fn (Blueprint $table) => $table->string('phone_norm')->nullable());
        $this->ensureColumn('leads', 'conversation_state', fn (Blueprint $table) => $table->string('conversation_state')->nullable());
        $this->ensureColumn('leads', 'is_active', fn (Blueprint $table) => $table->boolean('is_active')->default(true));
        $this->ensureColumn('leads', 'score', fn (Blueprint $table) => $table->integer('score')->nullable());
        $this->ensureColumn('clients', 'company_id', fn (Blueprint $table) => $table->unsignedBigInteger('company_id')->nullable());
        $this->ensureColumn('clients', 'phone_norm', fn (Blueprint $table) => $table->string('phone_norm')->nullable());
        $this->ensureColumn('clients', 'whatsapp', fn (Blueprint $table) => $table->string('whatsapp')->nullable());
        $this->ensureColumn('clients', 'email_norm', fn (Blueprint $table) => $table->string('email_norm')->nullable());

        if (! Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table) {
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
                $table->json('ai_analysis')->nullable();
                $table->decimal('ai_confidence', 5, 2)->nullable();
                $table->string('ai_intent')->nullable();
                $table->integer('ai_propensity_score')->nullable();
                $table->text('ai_propensity_reason')->nullable();
                $table->boolean('is_ai')->default(false);
                $table->timestamps();
            });
        }

        $this->ensureColumn('message_logs', 'conversation_id', fn (Blueprint $table) => $table->unsignedBigInteger('conversation_id')->nullable());
        $this->ensureColumn('message_logs', 'user_id', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable());
        $this->ensureColumn('message_logs', 'source', fn (Blueprint $table) => $table->string('source')->nullable());
        $this->ensureColumn('message_logs', 'template_id', fn (Blueprint $table) => $table->unsignedBigInteger('template_id')->nullable());
        $this->ensureColumn('message_logs', 'read_at', fn (Blueprint $table) => $table->timestamp('read_at')->nullable());
        $this->ensureColumn('message_logs', 'ai_analysis', fn (Blueprint $table) => $table->json('ai_analysis')->nullable());
        $this->ensureColumn('message_logs', 'ai_confidence', fn (Blueprint $table) => $table->decimal('ai_confidence', 5, 2)->nullable());
        $this->ensureColumn('message_logs', 'ai_intent', fn (Blueprint $table) => $table->string('ai_intent')->nullable());
        $this->ensureColumn('message_logs', 'ai_propensity_score', fn (Blueprint $table) => $table->integer('ai_propensity_score')->nullable());
        $this->ensureColumn('message_logs', 'ai_propensity_reason', fn (Blueprint $table) => $table->text('ai_propensity_reason')->nullable());
        $this->ensureColumn('message_logs', 'is_ai', fn (Blueprint $table) => $table->boolean('is_ai')->default(false));
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
