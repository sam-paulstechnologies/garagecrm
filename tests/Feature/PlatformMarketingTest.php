<?php

namespace Tests\Feature;

use App\Jobs\PlatformMarketing\ProcessPlatformInboundMessage;
use App\Models\PlatformMarketing\PlatformMarketingCampaign;
use App\Models\PlatformMarketing\PlatformMarketingCampaignRecipient;
use App\Models\PlatformMarketing\PlatformMarketingChannel;
use App\Models\PlatformMarketing\PlatformMarketingConversation;
use App\Models\PlatformMarketing\PlatformMarketingProspect;
use App\Models\PlatformMarketing\PlatformMarketingSegment;
use App\Models\System\Company;
use App\Models\User;
use App\Services\PlatformMarketing\Ai\PlatformSalesAgent;
use App\Services\PlatformMarketing\CampaignSafetyService;
use App\Services\PlatformMarketing\PlatformComplianceService;
use App\Services\PlatformMarketing\PlatformProspectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PlatformMarketingTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_marketing_pages_and_tenants_are_denied(): void
    {
        $this->get(route('super-admin.marketing.dashboard'))
            ->assertRedirect('/login');

        $superAdmin = $this->user('super_admin');
        $admin = $this->user('admin', $this->company()->id);
        $manager = $this->user('manager', $admin->company_id);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.marketing.dashboard'))
            ->assertOk()
            ->assertSee('Marketing Command Center');

        foreach ([
            'super-admin.marketing.prospects.index',
            'super-admin.marketing.prospects.create',
            'super-admin.marketing.segments.index',
            'super-admin.marketing.campaigns.index',
            'super-admin.marketing.campaigns.create',
            'super-admin.marketing.conversations.index',
            'super-admin.marketing.appointments.index',
            'super-admin.marketing.imports.index',
            'super-admin.marketing.templates.index',
            'super-admin.marketing.reports.index',
            'super-admin.marketing.channel.index',
            'super-admin.marketing.settings.index',
            'super-admin.marketing.suppression-list.index',
        ] as $routeName) {
            $this->actingAs($superAdmin)
                ->get(route($routeName))
                ->assertOk();
        }

        $this->actingAs($admin)
            ->get(route('super-admin.marketing.dashboard'))
            ->assertForbidden();

        $this->actingAs($manager)
            ->get(route('super-admin.marketing.prospects.index'))
            ->assertForbidden();
    }

    public function test_prospect_phone_normalization_prevents_duplicates(): void
    {
        $service = app(PlatformProspectService::class);

        $service->createOrUpdate([
            'business_name' => 'Alpha Garage',
            'whatsapp_number' => '052 742 7692',
            'status' => 'new',
            'consent_status' => 'opted_in',
        ], null, $this->user('super_admin')->id);

        $this->expectException(ValidationException::class);

        $service->createOrUpdate([
            'business_name' => 'Duplicate Garage',
            'whatsapp_number' => '+971 52 742 7692',
            'status' => 'new',
            'consent_status' => 'opted_in',
        ]);
    }

    public function test_campaign_preparation_enforces_consent_suppression_and_duplicate_recipient_guard(): void
    {
        $eligible = PlatformMarketingProspect::create([
            'business_name' => 'Eligible Garage',
            'whatsapp_number' => '+971527427692',
            'normalized_phone' => '971527427692',
            'status' => 'ready_to_contact',
            'consent_status' => 'opted_in',
        ]);

        $suppressed = PlatformMarketingProspect::create([
            'business_name' => 'Suppressed Garage',
            'whatsapp_number' => '+971500000001',
            'normalized_phone' => '971500000001',
            'status' => 'opted_out',
            'consent_status' => 'opted_out',
        ]);

        app(PlatformComplianceService::class)->optOut($suppressed);

        $segment = PlatformMarketingSegment::create(['name' => 'UAT Segment']);
        $segment->prospects()->sync([$eligible->id, $suppressed->id]);

        $campaign = PlatformMarketingCampaign::create([
            'name' => 'Founder UAT',
            'segment_id' => $segment->id,
            'template_name' => 'sayaraforce_intro_v1',
            'status' => 'draft',
            'batch_size' => 25,
            'delay_between_batches' => 300,
            'daily_cap' => 100,
        ]);

        $summary = app(CampaignSafetyService::class)->prepareRecipients($campaign);
        $secondSummary = app(CampaignSafetyService::class)->prepareRecipients($campaign->fresh());

        $this->assertSame(['eligible' => 1, 'suppressed' => 1, 'duplicates' => 0], $summary);
        $this->assertSame(['eligible' => 0, 'suppressed' => 1, 'duplicates' => 1], $secondSummary);
        $this->assertDatabaseHas('platform_marketing_campaign_recipients', [
            'campaign_id' => $campaign->id,
            'prospect_id' => $eligible->id,
            'status' => 'queued',
        ]);
    }

    public function test_platform_webhook_routes_only_matching_platform_context_and_keeps_garage_fallback(): void
    {
        Queue::fake();
        Config::set('services.meta.app_secret', 'test-secret');
        Config::set('services.meta_leads.app_secret', 'test-secret');

        $channel = PlatformMarketingChannel::create([
            'name' => 'PaulsTechnologies LLC',
            'display_phone_number' => '+971527427692',
            'phone_number_id' => '1070868312780019',
            'connection_status' => 'connected',
            'is_active' => true,
        ]);

        $prospect = PlatformMarketingProspect::create([
            'business_name' => 'Reply Garage',
            'whatsapp_number' => '+971586934377',
            'normalized_phone' => '971586934377',
            'status' => 'contacted',
            'consent_status' => 'opted_in',
        ]);

        $conversation = PlatformMarketingConversation::create([
            'prospect_id' => $prospect->id,
            'channel_id' => $channel->id,
            'state' => 'greeting',
        ]);

        $payload = $this->metaPayload('1070868312780019', '971586934377', 'wamid.platform.1', 'Hi');

        $this->postSignedMetaPayload($payload)
            ->assertNoContent();

        Queue::assertPushed(ProcessPlatformInboundMessage::class, fn ($job) => $job->context['conversation_id'] === $conversation->id);

        $garagePayload = $this->metaPayload('garage-phone-number-id', '971500000123', 'wamid.garage.1', 'Hi');

        $this->postSignedMetaPayload($garagePayload)
            ->assertNoContent();

        Queue::assertPushed(ProcessPlatformInboundMessage::class, 1);
    }

    public function test_ai_sales_agent_uses_deterministic_fallback_when_openai_is_unavailable(): void
    {
        Config::set('services.openai.api_key', '');

        $prospect = PlatformMarketingProspect::create([
            'business_name' => 'Demo Garage',
            'whatsapp_number' => '+971500000999',
            'normalized_phone' => '971500000999',
            'status' => 'replied',
            'consent_status' => 'opted_in',
        ]);

        $conversation = PlatformMarketingConversation::create([
            'prospect_id' => $prospect->id,
            'state' => 'greeting',
        ]);

        $reply = app(PlatformSalesAgent::class)->respond($conversation, 'Can I book a demo?');

        $this->assertSame('fallback', $reply['source']);
        $this->assertStringContainsString('SayaraForce demo', $reply['body']);
        $this->assertDatabaseHas('platform_marketing_ai_runs', [
            'conversation_id' => $conversation->id,
            'status' => 'fallback',
            'failure_reason' => 'missing_api_key',
        ]);
    }

    public function test_channel_page_never_renders_raw_credentials(): void
    {
        PlatformMarketingChannel::create([
            'name' => 'Secret Channel',
            'display_phone_number' => '+971527427692',
            'phone_number_id' => '1070868312780019',
            'access_token' => 'RAW_SECRET_SHOULD_NOT_RENDER',
            'verify_token' => 'VERIFY_SECRET_SHOULD_NOT_RENDER',
            'connection_status' => 'connected',
            'is_active' => true,
        ]);

        $this->actingAs($this->user('super_admin'))
            ->get(route('super-admin.marketing.channel.index'))
            ->assertOk()
            ->assertSee('+971527427692')
            ->assertSee('1070...0019')
            ->assertDontSee('RAW_SECRET_SHOULD_NOT_RENDER')
            ->assertDontSee('VERIFY_SECRET_SHOULD_NOT_RENDER');
    }

    private function postSignedMetaPayload(array $payload)
    {
        $json = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $json, 'test-secret');

        return $this->call(
            'POST',
            '/api/v1/webhooks/meta/whatsapp',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256' => $signature,
            ],
            $json
        );
    }

    private function metaPayload(string $phoneNumberId, string $from, string $messageId, string $body): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'metadata' => [
                            'phone_number_id' => $phoneNumberId,
                            'display_phone_number' => '+971527427692',
                        ],
                        'contacts' => [[
                            'profile' => ['name' => 'Sam Prospect'],
                            'wa_id' => $from,
                        ]],
                        'messages' => [[
                            'from' => $from,
                            'id' => $messageId,
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => $body],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    private function company(): Company
    {
        return Company::create([
            'name' => 'Tenant Garage',
            'email' => 'tenant@example.test',
            'phone' => '971500000000',
            'status' => 'active',
        ]);
    }

    private function user(string $role, ?int $companyId = null): User
    {
        return User::firstOrCreate(
            ['email' => $role.'-platform-marketing@example.test'],
            [
                'name' => str($role)->headline().' User',
                'password' => 'password',
                'role' => $role,
                'company_id' => $companyId,
                'status' => true,
                'must_change_password' => false,
            ]
        );
    }
}
