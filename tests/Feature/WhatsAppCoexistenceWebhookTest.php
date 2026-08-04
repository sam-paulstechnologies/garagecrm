<?php

namespace Tests\Feature;

use App\Jobs\ProcessInboundWhatsApp;
use App\Jobs\ProcessWhatsAppCoexistenceWebhook;
use App\Models\MessageLog;
use App\Models\System\Company;
use App\Models\WhatsApp\WhatsAppHistoryMessage;
use App\Models\WhatsApp\WhatsAppSyncedContact;
use App\Models\WhatsApp\WhatsAppWebhookEvent;
use App\Services\WhatsApp\MetaCoexistenceWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppCoexistenceWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.meta.app_secret' => 'test-meta-app-secret',
            'services.meta_leads.app_secret' => 'test-meta-app-secret',
        ]);
    }

    public function test_normal_inbound_message_is_dispatched_once_for_duplicate_delivery(): void
    {
        Queue::fake();
        $company = $this->company();
        $payload = $this->fixture('whatsapp-inbound.json');

        $this->signedPost($payload)->assertNoContent();
        $this->signedPost($payload)->assertNoContent();

        Queue::assertPushed(ProcessInboundWhatsApp::class, 1);
        Queue::assertPushed(ProcessInboundWhatsApp::class, function (ProcessInboundWhatsApp $job) use ($company) {
            return $job->companyId === $company->id
                && $job->provider === 'meta'
                && $job->payload['source_kind'] === 'customer_inbound';
        });
        $this->assertSame(1, WhatsAppWebhookEvent::query()->where('event_type', 'customer_inbound')->count());
    }

    public function test_business_app_echo_is_logged_once_as_outbound_and_never_enters_inbound_automation(): void
    {
        Queue::fake();
        $company = $this->company();
        $payload = $this->fixture('whatsapp-echo.json');

        $this->signedPost($payload)->assertNoContent();
        $this->signedPost($payload)->assertNoContent();

        Queue::assertNotPushed(ProcessInboundWhatsApp::class);
        $this->assertDatabaseCount('message_logs', 1);
        $this->assertDatabaseHas('message_logs', [
            'company_id' => $company->id,
            'provider_message_id' => 'wamid.echo.fixture',
            'direction' => 'out',
            'source' => 'whatsapp_business_app',
        ]);
        $this->assertNotNull($company->fresh()->whatsapp_last_echo_at);
    }

    public function test_contact_sync_is_tenant_scoped_encrypted_and_does_not_create_operational_records(): void
    {
        Queue::fake();
        $company = $this->company();

        $this->signedPost($this->fixture('whatsapp-contact-sync.json'))->assertNoContent();
        Queue::assertPushed(ProcessWhatsAppCoexistenceWebhook::class, 1);
        $event = WhatsAppWebhookEvent::query()->where('field', 'smb_app_state_sync')->firstOrFail();
        app(MetaCoexistenceWebhookService::class)->process($event->id);

        $contact = WhatsAppSyncedContact::query()->firstOrFail();
        $this->assertSame($company->id, $contact->company_id);
        $this->assertSame('971522222222', $contact->contact_phone);
        $stored = (string) DB::table('whatsapp_synced_contacts')->value('contact_phone');
        $this->assertStringNotContainsString('971522222222', $stored);
        $this->assertSame('completed', $company->fresh()->whatsapp_contact_sync_status);
        $this->assertOperationalTablesRemainEmpty();
    }

    public function test_history_sync_is_isolated_idempotent_encrypted_and_has_no_automation_side_effects(): void
    {
        Queue::fake();
        $company = $this->company();
        $payload = $this->fixture('whatsapp-history.json');

        $this->signedPost($payload)->assertNoContent();
        $this->signedPost($payload)->assertNoContent();
        Queue::assertPushed(ProcessWhatsAppCoexistenceWebhook::class, 1);
        Queue::assertNotPushed(ProcessInboundWhatsApp::class);
        $event = WhatsAppWebhookEvent::query()->where('field', 'history')->firstOrFail();
        app(MetaCoexistenceWebhookService::class)->process($event->id);
        app(MetaCoexistenceWebhookService::class)->process($event->id);

        $this->assertSame(1, WhatsAppHistoryMessage::query()->count());
        $history = WhatsAppHistoryMessage::query()->firstOrFail();
        $this->assertSame('An older synchronized message', $history->body);
        $rawBody = (string) DB::table('whatsapp_history_messages')->value('body');
        $this->assertStringNotContainsString('older synchronized message', $rawBody);
        $this->assertSame('completed', $company->fresh()->whatsapp_history_sync_status);
        $this->assertOperationalTablesRemainEmpty();
    }

    public function test_supported_media_dispatches_with_media_metadata_without_storing_raw_webhook(): void
    {
        Queue::fake();
        $this->company();
        $payload = $this->fixture('whatsapp-inbound.json');
        $message =& $payload['entry'][0]['changes'][0]['value']['messages'][0];
        $message['id'] = 'wamid.media.fixture';
        $message['type'] = 'image';
        unset($message['text']);
        $message['image'] = ['id' => 'media-100', 'mime_type' => 'image/jpeg', 'caption' => 'Damage photo'];

        $this->signedPost($payload)->assertNoContent();

        Queue::assertPushed(ProcessInboundWhatsApp::class, function (ProcessInboundWhatsApp $job) {
            return $job->numMedia === 1
                && $job->body === 'Damage photo'
                && $job->payload['media_id'] === 'media-100'
                && $job->payload['media_mime_type'] === 'image/jpeg';
        });
        $encrypted = (string) DB::table('whatsapp_webhook_events')->value('payload');
        $this->assertStringNotContainsString('Damage photo', $encrypted);
    }

    public function test_ambiguous_phone_claim_is_rejected_without_cross_company_delivery(): void
    {
        Queue::fake();
        $this->company(['name' => 'Tenant One']);
        $this->company(['name' => 'Tenant Two']);

        $this->signedPost($this->fixture('whatsapp-inbound.json'))->assertNoContent();

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('whatsapp_webhook_events', 0);
        $this->assertDatabaseCount('message_logs', 0);
    }

    public function test_out_of_order_status_does_not_regress_a_read_message(): void
    {
        $company = $this->company();
        $log = MessageLog::query()->create([
            'company_id' => $company->id,
            'direction' => 'out',
            'channel' => 'whatsapp',
            'source' => 'cloud_api',
            'provider_message_id' => 'wamid.status.order',
            'provider_status' => 'read',
            'body' => 'Operational reply',
        ]);
        $payload = $this->fixture('whatsapp-inbound.json');
        $value =& $payload['entry'][0]['changes'][0]['value'];
        unset($value['contacts'], $value['messages']);
        $value['statuses'] = [[
            'id' => 'wamid.status.order', 'status' => 'delivered', 'timestamp' => '1779999999',
        ]];

        $this->signedPost($payload)->assertNoContent();

        $this->assertSame('read', $log->fresh()->provider_status);
        $this->assertDatabaseHas('whatsapp_webhook_events', [
            'company_id' => $company->id,
            'event_type' => 'message_status',
            'status' => 'ignored',
            'error_code' => 'stale_status',
        ]);
    }

    private function signedPost(array $payload)
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'test-meta-app-secret');

        return $this->call('POST', route('api.webhooks.meta.whatsapp.handle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body);
    }

    private function fixture(string $name): array
    {
        return json_decode((string) file_get_contents(base_path('tests/Fixtures/Meta/'.$name)), true, flags: JSON_THROW_ON_ERROR);
    }

    private function company(array $overrides = []): Company
    {
        $company = Company::query()->create(array_merge(['name' => 'Coexistence Garage'], $overrides));
        $company->forceFill([
            'meta_waba_id' => '100200300',
            'meta_phone_number_id' => '400500600',
            'meta_display_phone_number' => '+971 50 000 0000',
            'is_whatsapp_active' => true,
            'whatsapp_connection_mode' => 'business_app_onboarding',
            'whatsapp_coexistence_enabled' => true,
        ])->save();

        return $company;
    }

    private function assertOperationalTablesRemainEmpty(): void
    {
        $this->assertDatabaseCount('message_logs', 0);
        if (Schema::hasTable('leads')) {
            $this->assertDatabaseCount('leads', 0);
        }
        if (Schema::hasTable('conversations')) {
            $this->assertDatabaseCount('conversations', 0);
        }
    }
}
