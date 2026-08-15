<?php

namespace Tests\Feature;

use App\Exceptions\WhatsAppOnboardingException;
use App\Jobs\ProcessQuickScanWebhook;
use App\Models\QuickScan\QuickScanProviderSession;
use App\Models\QuickScan\QuickScanWorkspace;
use App\Models\User;
use App\QuickScan\QuickScanAccess;
use App\QuickScan\QuickScanMeta;
use App\QuickScan\QuickScanPurge;
use App\QuickScan\QuickScanSyntheticFixture;
use Database\Seeders\CommercialFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class QuickScanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CommercialFoundationSeeder::class);
        config([
            'quick_scan.enabled' => true,
            'quick_scan.analysis_contact_limit' => 500,
            'quick_scan.decline_purge_delay_hours' => 24,
            'messaging.history.hmac_key' => 'quick-scan-test-hmac',
            'services.openai.api_key' => null,
            'services.meta.app_secret' => 'quick-scan-meta-secret',
            'services.meta_leads.app_secret' => 'quick-scan-meta-secret',
            'security.two_factor_enforcement' => 'off',
            'registration.public_enabled' => true,
        ]);
    }

    public function test_only_platform_user_can_create_secure_expiring_scan_and_qr_contains_no_prospect_data(): void
    {
        $platform = $this->platformUser();
        $tenant = User::query()->create([
            'name' => 'Tenant User', 'email' => 'tenant-quick-scan@example.test',
            'password' => 'Strong-Password-2026!', 'role' => 'admin', 'status' => true,
        ]);

        $this->actingAs($tenant)->get(route('super-admin.quick-scans.index'))->assertForbidden();
        $response = $this->actingAs($platform)->post(route('super-admin.quick-scans.store'), [
            'garage_name' => 'Synthetic Prospect Garage', 'garage_contact_name' => 'Synthetic Owner',
            'garage_phone' => '+971 50 000 8800', 'garage_email' => 'prospect@example.test',
            'source' => 'staging_test',
        ]);
        $scan = QuickScanWorkspace::query()->firstOrFail();
        $response->assertRedirect(route('super-admin.quick-scans.show', $scan));
        $this->assertSame(64, strlen(app(QuickScanAccess::class)->token($scan)));
        $pathToken = basename((string) parse_url(app(QuickScanAccess::class)->url($scan), PHP_URL_PATH));
        $this->assertSame(64, strlen($pathToken));
        $this->assertNotSame((string) $scan->id, $pathToken);
        $this->actingAs($platform)->get(route('super-admin.quick-scans.qr', $scan))
            ->assertOk()->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_public_consent_is_scoped_and_meta_can_remain_unconfigured_without_dead_page(): void
    {
        config(['services.meta.app_id' => null, 'services.meta.app_secret' => null]);
        ['scan' => $scan, 'token' => $token] = $this->scan();

        $this->get(route('quick-scan.show', ['token' => $token]))->assertOk()->assertSee('I understand and continue');
        $this->post(route('quick-scan.consent', ['token' => $token]), [
            'consent' => '1', 'connection_mode' => 'business_app_onboarding',
            'whatsapp_number' => '+971 50 000 8801', 'staff_numbers' => "+971 50 000 8811\n+971500008812",
        ])->assertRedirect();

        $scan->refresh();
        $this->assertSame('connection_pending', $scan->status);
        $this->assertNotNull($scan->consent_at);
        $this->assertCount(2, $scan->staff_number_hashes);
        $this->get(route('quick-scan.show', ['token' => $token]))
            ->assertOk()->assertSee('Meta connection is not configured');
        $this->postJson(route('quick-scan.meta.start', ['token' => $token]))->assertStatus(500);
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_synthetic_500_contact_scan_is_observational_bounded_real_and_idempotent(): void
    {
        $created = app(QuickScanSyntheticFixture::class)->create($this->platformUser(), 500);
        $scan = $created['scan']->fresh();
        $metrics = (array) $scan->report_metrics;

        $this->assertSame('report_ready', $scan->status);
        $this->assertSame(500, $scan->contacts_discovered);
        $this->assertSame(500, $scan->contacts_analysed);
        $this->assertSame(0, $scan->ai_calls);
        $this->assertSame(500, $metrics['conversations_analysed']);
        $this->assertGreaterThan(0, $metrics['likely_customers']);
        $this->assertGreaterThan(0, $metrics['potential_missed']);
        $this->assertGreaterThan(0, $metrics['high_retention']);
        $this->assertGreaterThan(0, $metrics['quotes_worth_reviewing']);
        $this->assertDatabaseCount('quick_scan_candidates', 500);
        $this->assertDatabaseCount('quick_scan_messages', 500);
        foreach (['companies', 'clients', 'leads', 'opportunities', 'bookings', 'billing_invoices'] as $table) {
            if (Schema::hasTable($table)) {
                $this->assertSame(0, DB::table($table)->count(), "{$table} must stay empty during Quick Scan.");
            }
        }

        app(\App\QuickScan\QuickScanAnalysis::class)->run($scan, allowExternalAi: false);
        $this->assertSame(500, $scan->fresh()->contacts_analysed);
        $this->assertDatabaseCount('quick_scan_candidates', 500);
    }

    public function test_internal_analysis_limit_never_uses_tenant_entitlement_usage(): void
    {
        config(['quick_scan.analysis_contact_limit' => 25]);
        $created = app(QuickScanSyntheticFixture::class)->create($this->platformUser(), 30);
        $scan = $created['scan']->fresh();

        $this->assertSame(25, $scan->contacts_analysed);
        $this->assertSame(5, $scan->candidates()->where('intelligence_status', 'locked')->count());
        $this->assertDatabaseCount('whatsapp_history_contact_usages', 0);
        $this->assertDatabaseCount('ai_customer_usages', 0);
    }

    public function test_quick_scan_webhook_history_is_routed_to_scan_queue_while_live_messages_are_ignored(): void
    {
        Queue::fake();
        ['scan' => $scan] = $this->scan();
        $scan->forceFill(['status' => 'history_syncing'])->save();
        QuickScanProviderSession::query()->create([
            'quick_scan_workspace_id' => $scan->id,
            'state_hash' => hash('sha256', 'state'), 'state_encrypted' => str_repeat('s', 64),
            'connection_mode' => 'business_app_onboarding', 'status' => 'completed',
            'waba_id' => '100200300', 'phone_number_id' => '400500600',
            'waba_hash' => hash_hmac('sha256', '100200300', (string) config('app.key')),
            'phone_number_hash' => hash_hmac('sha256', '400500600', (string) config('app.key')),
            'expires_at' => now()->addHour(), 'completed_at' => now(),
        ]);

        $this->signedWebhook($this->historyPayload())->assertNoContent();
        Queue::assertPushed(ProcessQuickScanWebhook::class, 1);
        $this->assertDatabaseHas('quick_scan_provider_events', ['field' => 'history', 'status' => 'pending']);

        $this->signedWebhook($this->messagePayload())->assertNoContent();
        Queue::assertPushed(ProcessQuickScanWebhook::class, 1);
        $this->assertDatabaseHas('quick_scan_provider_events', [
            'field' => 'messages', 'status' => 'ignored', 'error_code' => 'quick_scan_observational_only',
        ]);
        $this->assertDatabaseCount('clients', 0);
        $this->assertDatabaseCount('message_logs', 0);
    }

    public function test_meta_completion_reuses_verified_signup_contract_and_rejects_state_or_provider_id_injection(): void
    {
        config([
            'services.meta.app_id' => '123456789',
            'services.meta.app_secret' => 'quick-scan-meta-secret',
            'services.meta.whatsapp_embedded_signup.business_app_config_id' => 'config-test',
        ]);
        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'oauth/access_token')) {
                return Http::response(['access_token' => 'synthetic-test-access-token', 'token_type' => 'bearer'], 200);
            }
            if (str_contains($url, 'debug_token')) {
                return Http::response(['data' => [
                    'is_valid' => true, 'app_id' => '123456789',
                    'scopes' => ['whatsapp_business_management', 'whatsapp_business_messaging'],
                    'granular_scopes' => [['scope' => 'whatsapp_business_management', 'target_ids' => ['100200300']]],
                ]], 200);
            }
            if (str_contains($url, '100200300/phone_numbers')) {
                return Http::response(['data' => [[
                    'id' => '400500600', 'display_phone_number' => '+971 50 000 8801', 'is_on_biz_app' => true,
                ]]], 200);
            }
            if (str_contains($url, '/400500600/smb_app_data')) {
                return Http::response(['data' => ['request_id' => 'synthetic-request']], 200);
            }
            if (str_contains($url, '/400500600')) {
                return Http::response(['id' => '400500600', 'display_phone_number' => '+971 50 000 8801', 'is_on_biz_app' => true], 200);
            }
            if (str_contains($url, '100200300/subscribed_apps') && $request->method() === 'POST') {
                return Http::response(['success' => true], 200);
            }
            if (str_contains($url, '100200300/subscribed_apps')) {
                return Http::response(['data' => [['id' => '123456789']]], 200);
            }

            return Http::response([], 404);
        });

        ['scan' => $scan] = $this->scan();
        $scan->forceFill([
            'status' => 'connection_pending', 'consent_at' => now(),
            'connection_mode' => 'business_app_onboarding',
            'claimed_whatsapp_number' => '+971500008801',
        ])->save();
        $started = app(QuickScanMeta::class)->start($scan);
        $input = [
            'code' => 'temporary-auth-code', 'state' => $started['state'],
            'session_event' => 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING',
            'waba_id' => '100200300', 'phone_number_id' => '400500600', 'business_id' => null,
        ];
        $completed = app(QuickScanMeta::class)->complete($scan, $input);
        $this->assertSame('history_syncing', $completed->status);
        $this->assertDatabaseCount('messaging_connections', 0);
        $this->assertDatabaseCount('companies', 0);
        $this->assertSame('completed', $scan->providerSessions()->firstOrFail()->status);

        app(QuickScanMeta::class)->complete($scan, $input);
        $this->assertDatabaseCount('quick_scan_provider_sessions', 1);

        ['scan' => $injected] = $this->scan();
        $injected->forceFill([
            'status' => 'connection_pending', 'consent_at' => now(),
            'connection_mode' => 'business_app_onboarding', 'claimed_whatsapp_number' => '+971500008801',
        ])->save();
        $injectedStart = app(QuickScanMeta::class)->start($injected);
        try {
            app(QuickScanMeta::class)->complete($injected, [
                ...$input,
                'state' => $injectedStart['state'],
                'phone_number_id' => '999999999',
            ]);
            $this->fail('A browser-supplied phone identifier outside the verified WABA must be rejected.');
        } catch (WhatsAppOnboardingException $exception) {
            $this->assertSame('phone_not_in_waba', $exception->reason);
        }
        $this->assertSame('failed', $injected->providerSessions()->firstOrFail()->status);
        $this->assertDatabaseCount('messaging_connections', 0);

        ['scan' => $other] = $this->scan();
        $other->forceFill([
            'status' => 'connection_pending', 'consent_at' => now(),
            'connection_mode' => 'business_app_onboarding', 'claimed_whatsapp_number' => '+971500008801',
        ])->save();
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(QuickScanMeta::class)->complete($other, $input);
    }

    public function test_decline_then_purge_removes_customer_level_data_but_retains_aggregates_and_garage_prospect(): void
    {
        $created = app(QuickScanSyntheticFixture::class)->create($this->platformUser(), 20);
        $scan = app(QuickScanPurge::class)->decline($created['scan']);
        $this->assertSame('declined', $scan->status);
        $this->assertDatabaseCount('quick_scan_candidates', 20);

        $purged = app(QuickScanPurge::class)->purge($scan, force: true);
        $this->assertSame('purged', $purged->status);
        $this->assertDatabaseCount('quick_scan_candidates', 0);
        $this->assertDatabaseCount('quick_scan_messages', 0);
        $this->assertSame(20, data_get($purged->report_metrics, 'conversations_discovered'));
        $this->assertSame([], data_get($purged->report_metrics, 'evidence'));
        $this->assertNotNull($purged->garage_name);
        $this->get(route('quick-scan.show', ['token' => $created['token']]))->assertNotFound();
    }

    public function test_acceptance_prepares_normal_registration_and_handoff_without_clients_or_entitlement_bypass(): void
    {
        $created = app(QuickScanSyntheticFixture::class)->create($this->platformUser(), 8);
        $token = $created['token'];
        // M34: token is handed off via the server-side session, not the URL.
        $this->post(route('quick-scan.accept', ['token' => $token]))
            ->assertRedirect(route('register'))
            ->assertSessionHas(\App\Http\Controllers\QuickScanController::HANDOFF_SESSION_KEY, $token);
        $this->get(route('register'))->assertOk()->assertSee('History stays quarantined');
        $this->post(route('register'), [
            'garage_name' => 'Converted Quick Scan Garage', 'name' => 'Converted Admin',
            'email' => 'converted-quick-scan@example.test', 'phone' => '+971 50 000 8820',
            'address' => 'Synthetic Street, Dubai', 'password' => 'Strong-Password-2026!',
            'password_confirmation' => 'Strong-Password-2026!', 'terms' => '1',
            'quick_scan_token' => $token,
        ])->assertRedirect();

        $scan = $created['scan']->fresh();
        $this->assertNotNull($scan->converted_company_id);
        $this->assertDatabaseCount('clients', 0);
        if (Schema::hasTable('leads')) {
            $this->assertDatabaseCount('leads', 0);
        }
        $this->assertDatabaseCount('whatsapp_history_contact_usages', 0);
        $this->assertDatabaseCount('whatsapp_history_candidates', 8);
        $this->assertDatabaseHas('whatsapp_history_import_batches', ['company_id' => $scan->converted_company_id, 'status' => 'awaiting_review']);
    }

    public function test_invalid_expired_and_revoked_tokens_fail_closed(): void
    {
        ['scan' => $scan, 'token' => $token] = $this->scan();
        $this->get('/scan/'.str_repeat('x', 64))->assertNotFound();
        $scan->forceFill(['link_expires_at' => now()->subMinute()])->save();
        $this->get(route('quick-scan.show', ['token' => $token]))->assertNotFound();

        ['scan' => $scan2, 'token' => $token2] = $this->scan();
        app(QuickScanAccess::class)->revoke($scan2, $this->platformUser('platform-two@example.test'));
        $this->get(route('quick-scan.show', ['token' => $token2]))->assertNotFound();
    }

    private function platformUser(string $email = 'quick-scan-platform@example.test'): User
    {
        return User::query()->firstOrCreate(['email' => $email], [
            'name' => 'Quick Scan Platform Owner', 'password' => 'Strong-Password-2026!',
            'role' => 'super_admin', 'status' => true, 'must_change_password' => false,
        ]);
    }

    private function scan(): array
    {
        return app(QuickScanAccess::class)->create([
            'garage_name' => 'Isolated Quick Scan Garage', 'garage_contact_name' => 'Synthetic Owner',
            'garage_phone' => '+971 50 000 8800', 'garage_email' => 'isolated@example.test',
        ], $this->platformUser());
    }

    private function signedWebhook(array $payload)
    {
        $body = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = 'sha256='.hash_hmac('sha256', $body, 'quick-scan-meta-secret');

        return $this->call('POST', route('api.webhooks.meta.whatsapp.handle'), [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_HUB_SIGNATURE_256' => $signature,
        ], $body);
    }

    private function historyPayload(): array
    {
        return ['object' => 'whatsapp_business_account', 'entry' => [[
            'id' => '100200300', 'changes' => [['field' => 'history', 'value' => [
                'metadata' => ['phone_number_id' => '400500600'], 'status' => 'completed',
                'history' => [['status' => 'completed', 'threads' => []]],
            ]]],
        ]]];
    }

    private function messagePayload(): array
    {
        return ['object' => 'whatsapp_business_account', 'entry' => [[
            'id' => '100200300', 'changes' => [['field' => 'messages', 'value' => [
                'metadata' => ['phone_number_id' => '400500600'],
                'messages' => [['id' => 'wamid.quickscan.inbound', 'from' => '971500000001', 'type' => 'text', 'text' => ['body' => 'Hi']]],
            ]]],
        ]]];
    }
}
