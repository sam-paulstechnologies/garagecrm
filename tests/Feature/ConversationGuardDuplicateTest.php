<?php

namespace Tests\Feature;

use App\Models\Client\Lead;
use App\Models\MessageLog;
use App\Services\Conversation\ConversationGuard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConversationGuardDuplicateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareSchema();
    }

    public function test_same_message_repeated_within_duplicate_window_is_skipped(): void
    {
        Carbon::setTestNow('2026-07-20 10:00:05');

        $lead = $this->lead($this->company());
        $previous = $this->message($lead, 'Hi', 'wamid.previous', Carbon::parse('2026-07-20 10:00:00'));
        $current = $this->message($lead, 'hi', 'wamid.current', Carbon::parse('2026-07-20 10:00:05'));

        $this->assertTrue(app(ConversationGuard::class)->isDuplicateMessage($lead, 'hi', [
            'message_log_id' => $current->id,
            'message_logged_at' => $current->created_at,
            'provider_message_id' => 'wamid.current',
        ]));
    }

    public function test_same_message_after_fourteen_days_is_not_skipped(): void
    {
        Carbon::setTestNow('2026-07-20 10:00:00');

        $lead = $this->lead($this->company());
        $this->message($lead, 'Hi', 'wamid.july6', Carbon::parse('2026-07-06 10:00:00'));
        $current = $this->message($lead, 'hi', 'wamid.july20', Carbon::parse('2026-07-20 10:00:00'));

        $this->assertFalse(app(ConversationGuard::class)->isDuplicateMessage($lead, 'hi', [
            'message_log_id' => $current->id,
            'message_logged_at' => $current->created_at,
            'provider_message_id' => 'wamid.july20',
        ]));
    }

    public function test_future_previous_message_negative_difference_is_not_skipped(): void
    {
        Carbon::setTestNow('2026-07-20 10:00:00');

        $lead = $this->lead($this->company());
        $this->message($lead, 'Hi', 'wamid.future', Carbon::parse('2026-07-20 10:05:00'));
        $current = $this->message($lead, 'hi', 'wamid.current', Carbon::parse('2026-07-20 10:00:00'));

        $this->assertFalse(app(ConversationGuard::class)->isDuplicateMessage($lead, 'hi', [
            'message_log_id' => $current->id,
            'message_logged_at' => $current->created_at,
            'provider_message_id' => 'wamid.current',
        ]));
    }

    public function test_current_inbound_message_is_not_compared_against_itself(): void
    {
        Carbon::setTestNow('2026-07-20 10:00:00');

        $lead = $this->lead($this->company());
        $current = $this->message($lead, 'hi', 'wamid.only', Carbon::parse('2026-07-20 10:00:00'));

        $this->assertFalse(app(ConversationGuard::class)->isDuplicateMessage($lead, 'hi', [
            'message_log_id' => $current->id,
            'message_logged_at' => $current->created_at,
            'provider_message_id' => 'wamid.only',
        ]));
    }

    public function test_different_provider_sid_is_processed_unless_body_is_legitimately_duplicate(): void
    {
        Carbon::setTestNow('2026-07-20 10:00:05');

        $lead = $this->lead($this->company());
        $this->message($lead, 'Hi', 'wamid.previous', Carbon::parse('2026-07-20 10:00:00'));
        $current = $this->message($lead, 'Need service', 'wamid.current', Carbon::parse('2026-07-20 10:00:05'));

        $this->assertFalse(app(ConversationGuard::class)->isDuplicateMessage($lead, 'Need service', [
            'message_log_id' => $current->id,
            'message_logged_at' => $current->created_at,
            'provider_message_id' => 'wamid.current',
        ]));
    }

    public function test_other_company_messages_never_affect_duplicate_guard(): void
    {
        Carbon::setTestNow('2026-07-20 10:00:05');

        $companyA = $this->company('Garage A');
        $companyB = $this->company('Garage B');
        $leadA = $this->lead($companyA, '971500000001');
        $leadB = $this->lead($companyB, '971500000001');

        $this->message($leadA, 'Hi', 'wamid.company-a', Carbon::parse('2026-07-20 10:00:00'));
        $current = $this->message($leadB, 'hi', 'wamid.company-b', Carbon::parse('2026-07-20 10:00:05'));

        $this->assertFalse(app(ConversationGuard::class)->isDuplicateMessage($leadB, 'hi', [
            'message_log_id' => $current->id,
            'message_logged_at' => $current->created_at,
            'provider_message_id' => 'wamid.company-b',
        ]));
    }

    protected function company(string $name = 'Duplicate Guard Garage'): int
    {
        return (int) DB::table('companies')->insertGetId([
            'name' => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function lead(int $companyId, string $phone = '971500000001'): Lead
    {
        return Lead::withoutEvents(fn () => Lead::create([
            'company_id' => $companyId,
            'name' => 'Guard Customer',
            'phone' => $phone,
            'phone_norm' => preg_replace('/\D+/', '', $phone),
            'source' => 'whatsapp',
            'status' => Lead::STATUS_NEW,
            'preferred_channel' => 'whatsapp',
            'conversation_state' => 'idle',
            'conversation_data' => [],
        ]));
    }

    protected function message(Lead $lead, string $body, string $sid, Carbon $createdAt): MessageLog
    {
        $id = DB::table('message_logs')->insertGetId([
            'company_id' => $lead->company_id,
            'lead_id' => $lead->id,
            'direction' => 'in',
            'channel' => 'whatsapp',
            'from_number' => $lead->phone,
            'to_number' => '971400000000',
            'body' => $body,
            'provider_message_id' => $sid,
            'provider_status' => 'received',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        return MessageLog::findOrFail($id);
    }

    protected function prepareSchema(): void
    {
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

        $this->ensureMessageLogColumn('conversation_id', fn (Blueprint $table) => $table->unsignedBigInteger('conversation_id')->nullable());
    }

    protected function ensureMessageLogColumn(string $column, callable $definition): void
    {
        if (Schema::hasColumn('message_logs', $column)) {
            return;
        }

        Schema::table('message_logs', function (Blueprint $table) use ($definition) {
            $definition($table);
        });
    }
}
