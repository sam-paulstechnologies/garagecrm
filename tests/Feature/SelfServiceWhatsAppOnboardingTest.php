<?php

namespace Tests\Feature;

use App\Jobs\ProcessInboundWhatsApp;
use App\Messaging\Enums\ConnectionStatus;
use App\Messaging\Models\MessagingConnection;
use App\Messaging\Models\MessagingPhoneNumber;
use App\Messaging\WhatsApp\MetaApiClient;
use App\Models\System\Company;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SelfServiceWhatsAppOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('k', 32)),
            'messaging.default_product' => 'sayaraforce',
            'messaging.providers.meta_whatsapp.graph_base' => 'https://graph.test',
            'messaging.providers.meta_whatsapp.api_version' => 'v25.0',
            'messaging.providers.meta_whatsapp.app_id' => '925717083333434',
            'messaging.providers.meta_whatsapp.app_secret' => 'test-app-secret',
            'messaging.providers.meta_whatsapp.system_user_id' => null,
            'messaging.providers.meta_whatsapp.system_user_access_token' => null,
            'messaging.providers.meta_whatsapp.business_app_config_id' => 'business-config',
            'messaging.providers.meta_whatsapp.cloud_api_config_id' => 'cloud-config',
            'messaging.providers.meta_whatsapp.required_webhook_fields' => ['messages', 'smb_app_state_sync', 'smb_message_echoes'],
            'services.meta.app_secret' => 'test-app-secret',
            'services.meta_leads.app_secret' => 'test-app-secret',
        ]);
    }

    public function test_authorised_tenant_admin_can_start_onboarding_with_signed_state_nonce_and_consent(): void
    {
        [$company, $admin] = $this->tenant();

        $response = $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.start'), [
            'connection_mode' => 'business_app_onboarding',
            'consent_accepted' => true,
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('configuration.extras.featureType', 'whatsapp_business_app_onboarding')
            ->assertJsonPath('configuration.extras.sessionInfoVersion', '3');
        $this->assertNotEmpty($response->json('state'));
        $this->assertSame(48, strlen($response->json('nonce')));
        $this->assertDatabaseHas('messaging_onboarding_sessions', ['company_id' => $company->id, 'user_id' => $admin->id, 'status' => 'pending']);
        $this->assertDatabaseHas('messaging_consents', ['company_id' => $company->id, 'accepted_by' => $admin->id, 'consent_version' => '2026-08-phase-1']);
    }

    public function test_owner_page_is_plain_language_and_keeps_connection_modes_separate(): void
    {
        [, $admin] = $this->tenant();

        $this->actingAs($admin)->get(route('admin.messaging.whatsapp.index'))
            ->assertOk()
            ->assertSee('Connect your garage WhatsApp')
            ->assertSee('Connect existing Business app number')
            ->assertSee('Connect dedicated number')
            ->assertSee('SayaraForce will never ask for your Facebook password.')
            ->assertSee('business-config', false)
            ->assertSee('cloud-config', false)
            ->assertDontSee('access token')
            ->assertDontSee('subscribed_apps')
            ->assertDontSee('test-app-secret');

        $this->assertDatabaseCount('messaging_onboarding_sessions', 0);
    }

    public function test_manager_and_media_team_cannot_start_tenant_onboarding(): void
    {
        [$company] = $this->tenant();
        foreach (['manager', 'media_team'] as $role) {
            $user = User::factory()->create(['company_id' => $company->id, 'role' => $role, 'status' => true]);
            $this->actingAs($user)->postJson(route('admin.messaging.whatsapp.start'), [
                'connection_mode' => 'cloud_api', 'consent_accepted' => true,
            ])->assertForbidden();
        }
    }

    public function test_consent_is_required_before_meta_session_is_created(): void
    {
        [, $admin] = $this->tenant();
        $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.start'), [
            'connection_mode' => 'cloud_api', 'consent_accepted' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors('consent_accepted');
        $this->assertDatabaseCount('messaging_onboarding_sessions', 0);
        $this->assertDatabaseCount('messaging_consents', 0);
    }

    public function test_complete_flow_discovers_assets_encrypts_token_subscribes_checks_health_and_connects(): void
    {
        [$company, $admin] = $this->tenant();
        $payload = $this->start($admin);
        $this->fakeSuccessfulMeta();

        $response = $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.complete'), $this->completionPayload($payload));

        $response->assertOk()->assertJsonPath('status', 'connected')->assertJsonPath('idempotent', false);
        $connection = MessagingConnection::query()->firstOrFail();
        $this->assertSame($company->id, $connection->company_id);
        $this->assertSame('sayaraforce', $connection->product_key);
        $this->assertSame(ConnectionStatus::Connected, $connection->status);
        $this->assertDatabaseHas('messaging_phone_numbers', ['messaging_connection_id' => $connection->id, 'phone_number_id' => '300300']);
        $this->assertDatabaseHas('messaging_connection_checks', ['messaging_connection_id' => $connection->id, 'check_key' => 'app_subscription', 'status' => 'passed']);
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'meta_waba_id' => '200200', 'meta_phone_number_id' => '300300', 'is_whatsapp_active' => true]);
        $rawToken = (string) DB::table('messaging_connections')->value('encrypted_access_token');
        $legacyToken = (string) DB::table('companies')->value('meta_access_token');
        $this->assertStringNotContainsString('tenant-access-token', $rawToken);
        $this->assertStringNotContainsString('tenant-access-token', $legacyToken);
    }

    public function test_new_dedicated_cloud_api_path_remains_separate_and_connects_without_coexistence(): void
    {
        [, $admin] = $this->tenant();
        $payload = $this->start($admin, 'cloud_api');
        $this->fakeSuccessfulMeta(isOnBusinessApp: false);
        $completion = $this->completionPayload($payload);
        $completion['session_event'] = 'FINISH';

        $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.complete'), $completion)
            ->assertOk()->assertJsonPath('status', 'connected');

        $connection = MessagingConnection::query()->firstOrFail();
        $phone = MessagingPhoneNumber::query()->firstOrFail();
        $this->assertSame('cloud_api', $connection->connection_mode);
        $this->assertSame('not_applicable', $phone->coexistence_status);
        $this->assertFalse((bool) $connection->company->fresh()->whatsapp_coexistence_enabled);
    }

    public function test_new_connection_uses_existing_outbound_path_without_logging_phone_or_credentials(): void
    {
        [$company, $admin] = $this->tenant();
        $payload = $this->start($admin);
        $this->fakeSuccessfulMeta();
        $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.complete'), $this->completionPayload($payload))->assertOk();

        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged): void {
            $logged[] = ['message' => $event->message, 'context' => $event->context];
        });
        $result = app(WhatsAppService::class)->sendText('+971 50 123 4567', 'Mocked staff reply', ['company_id' => $company->id]);

        $this->assertSame('wamid.outbound.phase1', $result['message_id'] ?? null);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && str_contains($request->url(), '/300300/messages')
            && $request->hasHeader('Authorization'));
        $serializedLogs = json_encode($logged, JSON_UNESCAPED_SLASHES);
        $this->assertStringNotContainsString('tenant-access-token', $serializedLogs);
        $this->assertStringNotContainsString('300300', $serializedLogs);
        $this->assertStringNotContainsString('971501234567', preg_replace('/\D+/', '', $serializedLogs));
    }

    public function test_duplicate_completed_callback_is_idempotent_and_does_not_repeat_meta_writes(): void
    {
        [, $admin] = $this->tenant();
        $payload = $this->start($admin);
        $this->fakeSuccessfulMeta();
        $completion = $this->completionPayload($payload);

        $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.complete'), $completion)->assertOk();
        $requestCount = count(Http::recorded());
        $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.complete'), $completion)
            ->assertOk()->assertJsonPath('idempotent', true);

        $this->assertSame($requestCount, count(Http::recorded()));
        $this->assertDatabaseCount('messaging_connections', 1);
        $this->assertDatabaseCount('messaging_phone_numbers', 1);
    }

    public function test_tampered_state_and_nonce_are_rejected_before_meta_calls(): void
    {
        [, $admin] = $this->tenant();
        $payload = $this->start($admin);
        Http::fake();

        $invalidState = $this->completionPayload($payload);
        $invalidState['state'] .= 'x';
        $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.complete'), $invalidState)
            ->assertUnprocessable()->assertJsonPath('reason', 'invalid_state');

        $invalidNonce = $this->completionPayload($payload);
        $invalidNonce['nonce'] = str_repeat('x', 48);
        $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.complete'), $invalidNonce)
            ->assertUnprocessable()->assertJsonPath('reason', 'state_mismatch');
        Http::assertNothingSent();
    }

    public function test_expired_and_cross_tenant_callbacks_are_rejected_before_meta_calls(): void
    {
        [$company, $admin] = $this->tenant();
        $payload = $this->start($admin);
        DB::table('messaging_onboarding_sessions')->update(['expires_at' => now()->subMinute()]);
        Http::fake();
        $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.complete'), $this->completionPayload($payload))
            ->assertUnprocessable()->assertJsonPath('reason', 'expired_session');

        [, $otherAdmin] = $this->tenant(['name' => 'Other Garage'], ['email' => 'other@example.test']);
        $this->actingAs($otherAdmin)->postJson(route('admin.messaging.whatsapp.complete'), $this->completionPayload($payload))
            ->assertUnprocessable();
        Http::assertNothingSent();
        $this->assertDatabaseMissing('messaging_connections', ['company_id' => $company->id]);
    }

    public function test_waba_and_phone_ids_from_browser_must_match_server_discovery(): void
    {
        [, $admin] = $this->tenant();
        $payload = $this->start($admin);
        $this->fakeSuccessfulMeta();

        $completion = $this->completionPayload($payload);
        $completion['phone_number_id'] = '999999';
        $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.complete'), $completion)
            ->assertUnprocessable()->assertJsonPath('reason', 'phone_not_shared');
        $this->assertDatabaseCount('messaging_connections', 0);
    }

    public function test_nested_subscription_readback_is_required_and_connection_is_not_marked_connected_early(): void
    {
        [, $admin] = $this->tenant();
        $payload = $this->start($admin);
        $this->fakeSuccessfulMeta(subscriptionPayload: ['data' => [['id' => '999000']]]);

        $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.complete'), $this->completionPayload($payload))
            ->assertUnprocessable()->assertJsonPath('reason', 'subscription_readback_failed');

        $connection = MessagingConnection::query()->firstOrFail();
        $this->assertSame(ConnectionStatus::RequiresAction, $connection->status);
        $this->assertFalse((bool) $connection->company->is_whatsapp_active);
    }

    public function test_nested_subscription_parser_reads_whatsapp_business_api_data_id(): void
    {
        $client = app(MetaApiClient::class);
        $this->assertTrue($client->appIsSubscribed(['data' => [[
            'whatsapp_business_api_data' => ['id' => '925717083333434'],
        ]]]));
        $this->assertFalse($client->appIsSubscribed(['data' => [['id' => 'different-app']]]));
    }

    public function test_configured_platform_system_user_assignment_is_server_side_and_confirmed(): void
    {
        config([
            'messaging.providers.meta_whatsapp.system_user_id' => '700700',
            'messaging.providers.meta_whatsapp.system_user_access_token' => 'system-user-secret',
        ]);
        Http::fake(fn () => Http::response(['success' => true]));

        $this->assertTrue(app(MetaApiClient::class)->assignConfiguredSystemUser('200200'));
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && str_contains($request->url(), '/200200/assigned_users')
                && $request->hasHeader('Authorization')
                && ($request['user'] ?? null) === '700700';
        });
    }

    public function test_number_cannot_be_claimed_by_another_tenant(): void
    {
        [$firstCompany] = $this->tenant();
        $existing = MessagingConnection::query()->create([
            'company_id' => $firstCompany->id, 'product_key' => 'sayaraforce', 'provider' => 'meta_whatsapp',
            'status' => 'connected', 'waba_id' => '200999', 'encrypted_access_token' => 'safe-token',
        ]);
        MessagingPhoneNumber::query()->create([
            'messaging_connection_id' => $existing->id, 'provider' => 'meta_whatsapp', 'phone_number_id' => '300300', 'is_primary' => true,
        ]);
        [, $otherAdmin] = $this->tenant(['name' => 'Other Garage'], ['email' => 'other2@example.test']);
        $payload = $this->start($otherAdmin);
        $this->fakeSuccessfulMeta();

        $this->actingAs($otherAdmin)->postJson(route('admin.messaging.whatsapp.complete'), $this->completionPayload($payload))
            ->assertUnprocessable()->assertJsonPath('reason', 'number_already_connected');
        $this->assertDatabaseCount('messaging_phone_numbers', 1);
    }

    public function test_disconnect_is_local_audited_preserves_history_and_does_not_call_meta(): void
    {
        [$company, $admin] = $this->tenant();
        $connection = $this->connection($company, $admin, status: 'connected');
        $company->forceFill(['meta_phone_number_id' => '300300', 'is_whatsapp_active' => true])->save();
        Http::fake();

        $this->actingAs($admin)->post(route('admin.messaging.whatsapp.disconnect'), ['confirm_disconnect' => true])
            ->assertRedirect();

        $this->assertSame(ConnectionStatus::Disconnected, $connection->fresh()->status);
        $this->assertFalse((bool) $company->fresh()->is_whatsapp_active);
        $this->assertDatabaseHas('messaging_audit_logs', ['messaging_connection_id' => $connection->id, 'operation' => 'connection_disconnected_locally']);
        $this->assertDatabaseHas('messaging_phone_numbers', ['messaging_connection_id' => $connection->id, 'phone_number_id' => '300300']);
        Http::assertNothingSent();
    }

    public function test_generic_provider_id_routing_dispatches_sayaraforce_adapter_and_unknown_id_is_quarantined(): void
    {
        Queue::fake();
        [$company, $admin] = $this->tenant();
        $this->connection($company, $admin, status: 'connected');
        $payload = $this->webhookPayload('300300', '200200', 'wamid.generic.1');

        $this->signedWebhook($payload)->assertNoContent();
        Queue::assertPushed(ProcessInboundWhatsApp::class, fn ($job) => $job->companyId === $company->id
            && $job->payload['messaging_connection_id'] !== null
            && $job->payload['product_key'] === 'sayaraforce');

        $unknown = $this->webhookPayload('phone-unknown', 'waba-unknown', 'wamid.unknown.1');
        $this->signedWebhook($unknown)->assertNoContent();
        $this->assertDatabaseHas('messaging_webhook_events', ['status' => 'quarantined', 'error_code' => 'tenant_not_resolved']);
    }

    public function test_super_admin_can_view_diagnostics_and_other_roles_are_denied(): void
    {
        [$company, $admin] = $this->tenant();
        $connection = $this->connection($company, $admin, status: 'requires_action');
        $super = User::factory()->create(['role' => 'super_admin', 'status' => true]);

        $this->actingAs($super)->get(route('super-admin.messaging-connections.show', $connection))
            ->assertOk()->assertSee('Messaging Diagnostics')->assertDontSee('safe-token');
        $this->actingAs($admin)->get(route('super-admin.messaging-connections.show', $connection))->assertForbidden();
    }

    public function test_provider_secrets_and_authorization_codes_are_absent_from_logs_and_ui(): void
    {
        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged): void {
            $logged[] = ['message' => $event->message, 'context' => $event->context];
        });
        [, $admin] = $this->tenant();
        $payload = $this->start($admin);
        Http::fake(fn () => Http::response(['error' => ['code' => 190, 'message' => 'token tenant-access-token code temporary-code']], 400));

        $response = $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.complete'), $this->completionPayload($payload));
        $response->assertUnprocessable()->assertDontSee('tenant-access-token')->assertDontSee('temporary-code');
        $this->actingAs($admin)->get(route('admin.messaging.whatsapp.index'))->assertOk()->assertDontSee('test-app-secret')->assertDontSee('tenant-access-token');
        $serializedLogs = json_encode($logged, JSON_UNESCAPED_SLASHES);
        $this->assertStringNotContainsString('tenant-access-token', $serializedLogs);
        $this->assertStringNotContainsString('temporary-code', $serializedLogs);
        $this->assertStringNotContainsString('test-app-secret', $serializedLogs);
    }

    public function test_existing_smart_matrix_style_legacy_mapping_remains_unchanged_and_routes_inbound(): void
    {
        Queue::fake();
        $company = Company::query()->create(['name' => 'Smart Matrix Regression Fixture']);
        $company->forceFill([
            'meta_waba_id' => 'legacy-waba', 'meta_phone_number_id' => 'legacy-phone',
            'meta_access_token' => 'legacy-encrypted-placeholder', 'is_whatsapp_active' => true,
        ])->save();
        $before = $company->only(['meta_waba_id', 'meta_phone_number_id', 'meta_access_token']);

        $this->signedWebhook($this->webhookPayload('legacy-phone', 'legacy-waba', 'wamid.legacy.1'))->assertNoContent();
        Queue::assertPushed(ProcessInboundWhatsApp::class, fn ($job) => $job->companyId === $company->id);
        $this->assertSame($before, $company->fresh()->only(array_keys($before)));
        $this->assertDatabaseCount('messaging_connections', 0);
    }

    private function tenant(array $companyOverrides = [], array $userOverrides = []): array
    {
        $company = Company::query()->create(array_merge(['name' => 'Phase One Garage'], $companyOverrides));
        $user = User::factory()->create(array_merge([
            'company_id' => $company->id,
            'role' => 'admin',
            'status' => true,
            'must_change_password' => false,
        ], $userOverrides));

        return [$company, $user];
    }

    private function start(User $admin, string $mode = 'business_app_onboarding'): array
    {
        return $this->actingAs($admin)->postJson(route('admin.messaging.whatsapp.start'), [
            'connection_mode' => $mode,
            'consent_accepted' => true,
        ])->assertOk()->json();
    }

    private function completionPayload(array $start): array
    {
        return [
            'code' => 'temporary-code', 'state' => $start['state'], 'nonce' => $start['nonce'],
            'session_event' => 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING',
            'business_id' => '100100', 'waba_id' => '200200', 'phone_number_id' => '300300',
        ];
    }

    private function fakeSuccessfulMeta(
        array $subscriptionPayload = ['data' => [['whatsapp_business_api_data' => ['id' => '925717083333434']]]],
        bool $isOnBusinessApp = true,
    ): void
    {
        Http::fake(function (Request $request) use ($subscriptionPayload, $isOnBusinessApp) {
            $url = $request->url();
            return match (true) {
                str_contains($url, '/oauth/access_token') => Http::response(['access_token' => 'tenant-access-token', 'token_type' => 'bearer', 'expires_in' => 3600]),
                str_contains($url, '/debug_token') => Http::response(['data' => [
                    'is_valid' => true, 'app_id' => '925717083333434',
                    'scopes' => ['whatsapp_business_management', 'whatsapp_business_messaging'],
                    'granular_scopes' => [['scope' => 'whatsapp_business_management', 'target_ids' => ['200200']]],
                ]]),
                str_contains($url, '/200200/phone_numbers') => Http::response(['data' => [[
                    'id' => '300300', 'display_phone_number' => '+971 50 000 0000', 'verified_name' => 'Demo Workspace',
                    'quality_rating' => 'GREEN', 'status' => 'CONNECTED', 'code_verification_status' => 'VERIFIED',
                    'platform_type' => 'CLOUD_API', 'is_on_biz_app' => $isOnBusinessApp, 'name_status' => 'APPROVED',
                ]]]),
                $request->method() === 'POST' && str_contains($url, '/200200/subscribed_apps') => Http::response(['success' => true]),
                $request->method() === 'GET' && str_contains($url, '/200200/subscribed_apps') => Http::response($subscriptionPayload),
                $request->method() === 'POST' && str_contains($url, '/300300/messages') => Http::response([
                    'messaging_product' => 'whatsapp',
                    'messages' => [['id' => 'wamid.outbound.phase1']],
                ]),
                str_contains($url, '/925717083333434/subscriptions') => Http::response(['data' => [[
                    'object' => 'whatsapp_business_account',
                    'fields' => [['name' => 'messages'], ['name' => 'smb_app_state_sync'], ['name' => 'smb_message_echoes']],
                ]]]),
                str_contains($url, '/100100') => Http::response(['id' => '100100', 'name' => 'Demo Business']),
                str_contains($url, '/300300') => Http::response([
                    'id' => '300300', 'status' => 'CONNECTED', 'code_verification_status' => 'VERIFIED',
                    'is_on_biz_app' => $isOnBusinessApp, 'quality_rating' => 'GREEN',
                ]),
                str_contains($url, '/200200') => Http::response(['id' => '200200', 'name' => 'Demo WABA', 'owner_business_info' => ['id' => '100100']]),
                default => Http::response(['error' => ['code' => 404]], 404),
            };
        });
    }

    private function connection(Company $company, User $user, string $status): MessagingConnection
    {
        $connection = MessagingConnection::query()->create([
            'company_id' => $company->id, 'product_key' => 'sayaraforce', 'provider' => 'meta_whatsapp',
            'status' => $status, 'connection_mode' => 'business_app_onboarding', 'waba_id' => '200200',
            'encrypted_access_token' => 'safe-token', 'created_by' => $user->id, 'updated_by' => $user->id,
        ]);
        MessagingPhoneNumber::query()->create([
            'messaging_connection_id' => $connection->id, 'provider' => 'meta_whatsapp',
            'phone_number_id' => '300300', 'display_phone_number' => '+971 50 000 0000',
            'registration_status' => 'CONNECTED', 'coexistence_status' => 'connected', 'is_primary' => true,
        ]);

        return $connection;
    }

    private function webhookPayload(string $phoneId, string $wabaId, string $messageId): array
    {
        return ['object' => 'whatsapp_business_account', 'entry' => [[
            'id' => $wabaId,
            'changes' => [['field' => 'messages', 'value' => [
                'metadata' => ['phone_number_id' => $phoneId, 'display_phone_number' => '971500000000'],
                'contacts' => [['wa_id' => '971511111111', 'profile' => ['name' => 'Demo Contact']]],
                'messages' => [['id' => $messageId, 'from' => '971511111111', 'timestamp' => '1779999999', 'type' => 'text', 'text' => ['body' => 'Service enquiry']]],
            ]]],
        ]]];
    }

    private function signedWebhook(array $payload)
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        return $this->call('POST', route('api.webhooks.meta.whatsapp.handle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'test-app-secret'),
        ], $body);
    }
}
