<?php

namespace Tests\Unit;

use App\Logging\RedactSensitiveLogs;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;

class RedactSensitiveLogsTest extends TestCase
{
    private TestHandler $handler;

    private Logger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = new TestHandler();
        $this->logger = new Logger('test', [$this->handler]);

        // Attach the redaction processor exactly as the application does.
        (new RedactSensitiveLogs())($this->logger);
    }

    private function log(string $message, array $context = []): LogRecord
    {
        $this->logger->info($message, $context);

        $records = $this->handler->getRecords();

        return $records[array_key_last($records)];
    }

    public function test_phone_key_value_is_redacted(): void
    {
        $record = $this->log('login', ['phone' => '+971501234567']);

        $this->assertSame('[REDACTED]', $record->context['phone']);
    }

    public function test_phone_variant_keys_are_redacted(): void
    {
        $record = $this->log('login', [
            'msisdn' => '971501234567',
            'contact_phone' => '+971501234567',
            'whatsapp' => '+971501234567',
        ]);

        $this->assertSame('[REDACTED]', $record->context['msisdn']);
        $this->assertSame('[REDACTED]', $record->context['contact_phone']);
        $this->assertSame('[REDACTED]', $record->context['whatsapp']);
    }

    public function test_email_key_value_is_redacted(): void
    {
        $record = $this->log('signup', ['email' => 'jane.doe@example.com']);

        $this->assertSame('[REDACTED]', $record->context['email']);
    }

    public function test_message_and_body_keys_are_redacted(): void
    {
        $record = $this->log('inbound', [
            'message' => 'Hello there, this is private',
            'body' => 'raw payload',
            'message_body' => 'nested payload',
        ]);

        $this->assertSame('[REDACTED]', $record->context['message']);
        $this->assertSame('[REDACTED]', $record->context['body']);
        $this->assertSame('[REDACTED]', $record->context['message_body']);
    }

    public function test_name_keys_are_redacted(): void
    {
        $record = $this->log('profile', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'full_name' => 'Jane Doe',
            'display_name' => 'jdoe',
        ]);

        $this->assertSame('[REDACTED]', $record->context['first_name']);
        $this->assertSame('[REDACTED]', $record->context['last_name']);
        $this->assertSame('[REDACTED]', $record->context['full_name']);
        $this->assertSame('[REDACTED]', $record->context['display_name']);
    }

    public function test_email_inside_free_text_value_is_redacted(): void
    {
        // Key name is benign, so this only passes via value-pattern redaction.
        $record = $this->log('note', ['note' => 'Reach the customer at jane.doe@example.com asap']);

        $this->assertStringNotContainsString('jane.doe@example.com', $record->context['note']);
        $this->assertStringContainsString('[REDACTED]', $record->context['note']);
        $this->assertStringContainsString('Reach the customer at', $record->context['note']);
    }

    public function test_phone_inside_free_text_value_is_redacted(): void
    {
        $record = $this->log('note', ['note' => 'Called +971501234567 twice, no answer']);

        $this->assertStringNotContainsString('971501234567', $record->context['note']);
        $this->assertStringContainsString('[REDACTED]', $record->context['note']);
    }

    public function test_email_and_phone_in_log_message_are_redacted(): void
    {
        $record = $this->log('User jane.doe@example.com called from +971501234567');

        $this->assertStringNotContainsString('jane.doe@example.com', $record->message);
        $this->assertStringNotContainsString('971501234567', $record->message);
    }

    public function test_openai_key_in_value_is_redacted(): void
    {
        // Build an OpenAI-key-shaped token at RUNTIME so no complete
        // scanner-matchable credential literal exists in source (CI secret gate).
        // Concatenation keeps 'sk-' and the tail as separate string literals, yet
        // the assembled value still exercises the exact sk-/sk-proj- redaction path.
        $syntheticKey = 'sk-'.'proj-'.str_repeat('A', 24);

        $record = $this->log('llm', ['note' => "header key {$syntheticKey} used"]);

        // Sanity: the assembled token is genuinely OpenAI-key-shaped.
        $this->assertMatchesRegularExpression('/\bsk-(?:proj-)?[A-Za-z0-9_-]{20,}\b/', $syntheticKey);
        $this->assertStringNotContainsString($syntheticKey, $record->context['note']);
        $this->assertStringContainsString('[REDACTED]', $record->context['note']);
    }

    public function test_short_numeric_ids_are_not_redacted(): void
    {
        $record = $this->log('lookup', [
            'id' => 123,
            'company_id' => 5,
            'ref' => '4210',
        ]);

        $this->assertSame(123, $record->context['id']);
        $this->assertSame(5, $record->context['company_id']);
        $this->assertSame('4210', $record->context['ref']);
    }

    public function test_existing_secret_and_token_keys_still_redacted(): void
    {
        $record = $this->log('auth', [
            'token' => 'abc123',
            'secret' => 'topsecret',
            'authorization' => 'Basic xyz',
            'signature' => 'deadbeef',
        ]);

        $this->assertSame('[REDACTED]', $record->context['token']);
        $this->assertSame('[REDACTED]', $record->context['secret']);
        $this->assertSame('[REDACTED]', $record->context['authorization']);
        $this->assertSame('[REDACTED]', $record->context['signature']);
    }

    public function test_bearer_token_in_value_is_redacted(): void
    {
        $record = $this->log('req', ['note' => 'Authorization: Bearer aGVsbG8ud29ybGQ.token123']);

        $this->assertStringNotContainsString('aGVsbG8ud29ybGQ.token123', $record->context['note']);
        $this->assertStringContainsString('[REDACTED]', $record->context['note']);
    }

    public function test_nested_context_arrays_are_redacted(): void
    {
        $record = $this->log('nested', [
            'outer' => ['inner' => ['phone' => '+971501234567', 'id' => 7]],
        ]);

        $this->assertSame('[REDACTED]', $record->context['outer']['inner']['phone']);
        $this->assertSame(7, $record->context['outer']['inner']['id']);
    }

    public function test_processor_does_not_break_plain_messages(): void
    {
        $record = $this->log('ordinary log line with id 42');

        $this->assertSame('ordinary log line with id 42', $record->message);
    }
}
