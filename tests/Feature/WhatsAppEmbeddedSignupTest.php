<?php

namespace Tests\Feature;

use App\Exceptions\WhatsAppOnboardingException;
use App\Models\System\Company;
use App\Models\User;
use App\Services\WhatsApp\MetaEmbeddedSignupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppEmbeddedSignupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.meta.app_id' => '925717083333434',
            'services.meta.app_secret' => 'test-app-secret',
            'services.meta.api_version' => 'v25.0',
            'services.meta.whatsapp_verify_token' => 'global-webhook-test-token',
            'services.meta.whatsapp_embedded_signup.version' => 'v4',
            'services.meta.whatsapp_embedded_signup.session_info_version' => '3',
            'services.meta.whatsapp_embedded_signup.business_app_config_id' => 'business-app-config',
            'services.meta.whatsapp_embedded_signup.cloud_api_config_id' => 'cloud-api-config',
        ]);
    }

    public function test_business_app_page_uses_v4_current_feature_mode_and_never_deprecated_modes(): void
    {
        [$company, $user] = $this->tenant();

        $response = $this->actingAs($user)->get(route('admin.whatsapp.connect', [
            'mode' => MetaEmbeddedSignupService::MODE_BUSINESS_APP,
        ]));

        $response->assertOk()
            ->assertSee('whatsapp_business_app_onboarding', false)
            ->assertSee('sessionInfoVersion', false)
            ->assertSee('override_default_response_type', false)
            ->assertDontSee('featureType&quot;:&quot;coexistence', false)
            ->assertDontSee('featureType&quot;:&quot;whatsapp_embedded_signup', false)
            ->assertDontSee('test-app-secret', false);

        $this->assertDatabaseHas('whatsapp_connect_sessions', [
            'company_id' => $company->id,
            'user_id' => $user->id,
            'connection_mode' => MetaEmbeddedSignupService::MODE_BUSINESS_APP,
            'status' => 'started',
        ]);
    }

    public function test_business_app_configuration_does_not_fall_back_to_cloud_configuration(): void
    {
        config(['services.meta.whatsapp_embedded_signup.business_app_config_id' => null]);

        $config = app(MetaEmbeddedSignupService::class)
            ->signupConfiguration(MetaEmbeddedSignupService::MODE_BUSINESS_APP);

        $this->assertFalse($config['is_configured']);
        $this->assertNull($config['config_id']);
        $this->assertSame('whatsapp_business_app_onboarding', $config['extras']['featureType']);
    }

    public function test_connection_page_uses_semantic_theme_components_and_safe_coexistence_wording(): void
    {
        [$company, $user] = $this->tenant();
        $company->forceFill([
            'meta_phone_number_id' => 'phone-theme-test',
            'meta_access_token' => Crypt::encryptString('theme-test-token'),
            'is_whatsapp_active' => true,
        ])->save();
        config(['services.meta.whatsapp_embedded_signup.business_app_config_id' => null]);

        $response = $this->actingAs($user)->get(route('admin.whatsapp.connect', [
            'mode' => MetaEmbeddedSignupService::MODE_BUSINESS_APP,
        ]));

        $response->assertOk()
            ->assertSee('class="sf-page"', false)
            ->assertSee('class="sf-card overflow-hidden"', false)
            ->assertSee('sf-mini-card', false)
            ->assertSee('sf-alert-warning', false)
            ->assertSee('sf-btn-primary', false)
            ->assertSee('Diagnostics and synchronization')
            ->assertSee('sf-btn-danger', false)
            ->assertSee('data-theme-preference', false)
            ->assertSee('prefers-color-scheme: dark', false)
            ->assertSee(
                'Only continue if Meta confirms that your WhatsApp Business app will remain active. Cancel the setup if Meta asks to remove, transfer or migrate the number away from the app.'
            )
            ->assertDontSee('sf-surface-elevated,#101a2b', false)
            ->assertDontSee('bg-slate-950/45', false);
    }

    public function test_diagnostics_treats_missing_waba_as_pending_onboarding_without_calling_meta(): void
    {
        [$company, $user] = $this->tenant();
        $company->forceFill([
            'meta_phone_number_id' => 'legacy-phone-id',
            'meta_access_token' => Crypt::encryptString('legacy-encrypted-token'),
            'meta_waba_id' => null,
            'is_whatsapp_active' => true,
        ])->save();
        Http::fake();

        $this->actingAs($user)
            ->post(route('admin.whatsapp.connect.diagnostics'))
            ->assertRedirect()
            ->assertSessionHas('success', 'WABA subscription will be verified after onboarding');

        Http::assertNothingSent();
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'whatsapp_webhook_subscription_status' => 'pending_onboarding',
        ]);

        $this->actingAs($user)
            ->get(route('admin.whatsapp.connect'))
            ->assertOk()
            ->assertSee('App-level callback URL verification')
            ->assertSee('Ready for Meta verification')
            ->assertSee('WABA subscription will be verified after onboarding')
            ->assertDontSee('global-webhook-test-token');
    }

    public function test_diagnostics_confirms_successful_waba_subscribed_apps_check(): void
    {
        [$company, $user] = $this->tenant();
        $company->forceFill([
            'meta_phone_number_id' => 'phone-100',
            'meta_access_token' => Crypt::encryptString('diagnostic-token'),
            'meta_waba_id' => 'waba-100',
            'is_whatsapp_active' => true,
            'whatsapp_connection_mode' => MetaEmbeddedSignupService::MODE_BUSINESS_APP,
        ])->save();

        Http::fake(function (Request $request) {
            return match (true) {
                str_contains($request->url(), '/phone-100') => Http::response([
                    'id' => 'phone-100',
                    'status' => 'CONNECTED',
                    'quality_rating' => 'GREEN',
                    'is_on_biz_app' => true,
                ]),
                $request->method() === 'GET' && str_contains($request->url(), '/waba-100/subscribed_apps') => Http::response([
                    'data' => [[
                        'whatsapp_business_api_data' => [
                            'id' => '925717083333434',
                            'name' => 'SayaraForce',
                        ],
                    ]],
                ]),
                default => Http::response([], 404),
            };
        });

        $this->actingAs($user)
            ->postJson(route('admin.whatsapp.connect.diagnostics'))
            ->assertOk()
            ->assertJsonPath('diagnostics.callback_verification', 'ready')
            ->assertJsonPath('diagnostics.webhook_subscription', 'subscribed');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'whatsapp_webhook_subscription_status' => 'subscribed',
        ]);
        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && str_contains($request->url(), '/waba-100/subscribed_apps'));
    }

    public function test_subscription_repair_retries_until_meta_nested_app_id_is_confirmed(): void
    {
        [$company, $user] = $this->tenant();
        $company->forceFill([
            'meta_phone_number_id' => 'phone-100',
            'meta_access_token' => Crypt::encryptString('repair-token'),
            'meta_waba_id' => 'waba-100',
            'is_whatsapp_active' => true,
            'whatsapp_connection_mode' => MetaEmbeddedSignupService::MODE_BUSINESS_APP,
            'whatsapp_webhook_subscription_status' => 'missing',
        ])->save();

        $readAttempts = 0;
        Http::fake(function (Request $request) use (&$readAttempts) {
            if ($request->method() === 'POST' && str_contains($request->url(), '/waba-100/subscribed_apps')) {
                return Http::response(['success' => true]);
            }

            if ($request->method() === 'GET' && str_contains($request->url(), '/waba-100/subscribed_apps')) {
                $readAttempts++;

                return Http::response($readAttempts === 1 ? ['data' => []] : [
                    'data' => [[
                        'whatsapp_business_api_data' => [
                            'id' => '925717083333434',
                            'name' => 'SayaraForce',
                        ],
                    ]],
                ]);
            }

            return Http::response([], 404);
        });

        $result = app(MetaEmbeddedSignupService::class)
            ->repairWabaSubscription($company, $user->id, maxAttempts: 3, backoffMilliseconds: 0);

        $this->assertSame('subscribed', $result['status']);
        $this->assertSame(200, data_get($result, 'post.http_status'));
        $this->assertSame(200, data_get($result, 'verification.http_status'));
        $this->assertSame(2, $result['attempts']);
        $this->assertSame('subscribed', $company->fresh()->whatsapp_webhook_subscription_status);
        $this->assertDatabaseHas('whatsapp_connection_audits', [
            'company_id' => $company->id,
            'event' => 'waba_subscription_repaired',
            'status' => 'success',
        ]);
    }

    public function test_subscription_repair_never_marks_subscribed_without_meta_readback_confirmation(): void
    {
        [$company, $user] = $this->tenant();
        $company->forceFill([
            'meta_phone_number_id' => 'phone-100',
            'meta_access_token' => Crypt::encryptString('repair-token'),
            'meta_waba_id' => 'waba-100',
            'is_whatsapp_active' => true,
            'whatsapp_connection_mode' => MetaEmbeddedSignupService::MODE_BUSINESS_APP,
            'whatsapp_webhook_subscription_status' => 'missing',
        ])->save();

        Http::fake(function (Request $request) {
            return $request->method() === 'POST'
                ? Http::response(['success' => true])
                : Http::response(['data' => []]);
        });

        $result = app(MetaEmbeddedSignupService::class)
            ->repairWabaSubscription($company, $user->id, maxAttempts: 2, backoffMilliseconds: 0);

        $this->assertSame('pending_verification', $result['status']);
        $this->assertSame(2, $result['attempts']);
        $this->assertSame('pending_verification', $company->fresh()->whatsapp_webhook_subscription_status);
        $this->assertDatabaseHas('whatsapp_connection_audits', [
            'company_id' => $company->id,
            'event' => 'waba_subscription_repaired',
            'status' => 'warning',
        ]);
    }

    public function test_diagnostics_reports_failed_waba_subscribed_apps_check_without_provider_details(): void
    {
        [$company, $user] = $this->tenant();
        $company->forceFill([
            'meta_phone_number_id' => 'phone-100',
            'meta_access_token' => Crypt::encryptString('diagnostic-token'),
            'meta_waba_id' => 'waba-100',
            'is_whatsapp_active' => true,
            'whatsapp_connection_mode' => MetaEmbeddedSignupService::MODE_BUSINESS_APP,
        ])->save();

        Http::fake(function (Request $request) {
            return str_contains($request->url(), '/phone-100')
                ? Http::response(['id' => 'phone-100', 'status' => 'CONNECTED'])
                : Http::response(['error' => ['message' => 'private-provider-detail']], 500);
        });

        $this->actingAs($user)
            ->postJson(route('admin.whatsapp.connect.diagnostics'))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Meta could not verify the WABA app subscription.')
            ->assertDontSee('private-provider-detail')
            ->assertDontSee('diagnostic-token');
    }

    public function test_authorization_completion_validates_assets_subscribes_and_encrypts_tenant_credentials(): void
    {
        [$company, $user] = $this->tenant();
        $this->fakeMetaCompletion(isOnBusinessApp: true);
        $service = app(MetaEmbeddedSignupService::class);
        $state = $service->createState($company->id, $user->id, MetaEmbeddedSignupService::MODE_BUSINESS_APP);

        $result = $service->complete($company, $user->id, $this->completionPayload($state));

        $company->refresh();
        $this->assertFalse($result['idempotent']);
        $this->assertSame('business_app_onboarding', $company->whatsapp_connection_mode);
        $this->assertTrue($company->whatsapp_coexistence_enabled);
        $this->assertSame('waba-100', $company->meta_waba_id);
        $this->assertSame('phone-100', $company->meta_phone_number_id);
        $this->assertNotSame('short-lived-token', $company->meta_access_token);
        $this->assertSame('short-lived-token', Crypt::decryptString($company->meta_access_token));
        $this->assertDatabaseHas('whatsapp_connect_sessions', ['state' => $state, 'status' => 'completed']);
        $this->assertDatabaseHas('whatsapp_connection_audits', ['company_id' => $company->id, 'event' => 'signup_completed']);

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && str_contains($request->url(), '/waba-100/subscribed_apps'));
        Http::assertSent(fn (Request $request) => $request->method() === 'GET'
            && str_contains($request->url(), '/waba-100/subscribed_apps'));
        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && str_contains($request->url(), '/phone-100/smb_app_data'));
    }

    public function test_failed_code_exchange_is_safe_and_does_not_persist_credentials(): void
    {
        [$company, $user] = $this->tenant();
        Http::fake(['*oauth/access_token*' => Http::response(['error' => ['message' => 'sensitive provider detail']], 400)]);
        $service = app(MetaEmbeddedSignupService::class);
        $state = $service->createState($company->id, $user->id, MetaEmbeddedSignupService::MODE_BUSINESS_APP);

        try {
            $service->complete($company, $user->id, $this->completionPayload($state));
            $this->fail('Expected onboarding to be rejected.');
        } catch (WhatsAppOnboardingException $exception) {
            $this->assertSame('code_exchange_failed', $exception->reason);
            $this->assertStringNotContainsString('sensitive provider detail', $exception->getMessage());
        }

        $this->assertDatabaseHas('whatsapp_connect_sessions', ['state' => $state, 'status' => 'failed']);
        $this->assertNull($company->fresh()->meta_access_token);
    }

    public function test_wrong_or_missing_business_app_session_information_is_rejected_before_meta_calls(): void
    {
        [$company, $user] = $this->tenant();
        Http::fake();
        $service = app(MetaEmbeddedSignupService::class);
        $state = $service->createState($company->id, $user->id, MetaEmbeddedSignupService::MODE_BUSINESS_APP);

        $this->expectException(WhatsAppOnboardingException::class);
        try {
            $service->complete($company, $user->id, array_merge($this->completionPayload($state), [
                'session_event' => 'FINISH',
            ]));
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_phone_must_belong_to_validated_waba_and_cannot_be_claimed_cross_tenant(): void
    {
        [$company, $user] = $this->tenant();
        $other = Company::query()->create(['name' => 'Other Tenant']);
        $other->forceFill(['meta_phone_number_id' => 'phone-100'])->save();
        $this->fakeMetaCompletion(isOnBusinessApp: true);
        $service = app(MetaEmbeddedSignupService::class);
        $state = $service->createState($company->id, $user->id, MetaEmbeddedSignupService::MODE_BUSINESS_APP);

        $this->expectException(WhatsAppOnboardingException::class);
        $this->expectExceptionMessage('already connected to another SayaraForce company');
        $service->complete($company, $user->id, $this->completionPayload($state));
    }

    public function test_repeated_completed_callback_is_idempotent_and_does_not_repeat_meta_calls(): void
    {
        [$company, $user] = $this->tenant();
        $this->fakeMetaCompletion(isOnBusinessApp: true);
        $service = app(MetaEmbeddedSignupService::class);
        $state = $service->createState($company->id, $user->id, MetaEmbeddedSignupService::MODE_BUSINESS_APP);
        $payload = $this->completionPayload($state);

        $service->complete($company, $user->id, $payload);
        $requestCount = count(Http::recorded());
        $second = $service->complete($company->fresh(), $user->id, $payload);

        $this->assertTrue($second['idempotent']);
        $this->assertCount($requestCount, Http::recorded());
        $this->assertSame(1, Company::query()->where('meta_phone_number_id', 'phone-100')->count());
    }

    public function test_delayed_subscription_readback_completes_connection_as_pending_and_remains_idempotent(): void
    {
        [$company, $user] = $this->tenant();
        $this->fakeMetaCompletion(isOnBusinessApp: true, subscriptionReadback: ['data' => []]);
        $service = app(MetaEmbeddedSignupService::class);
        $state = $service->createState($company->id, $user->id, MetaEmbeddedSignupService::MODE_BUSINESS_APP);
        $payload = $this->completionPayload($state);

        $result = $service->complete($company, $user->id, $payload);

        $company->refresh();
        $this->assertFalse($result['idempotent']);
        $this->assertSame('pending_verification', $company->whatsapp_webhook_subscription_status);
        $this->assertSame('business_app_onboarding', $company->whatsapp_connection_mode);
        $this->assertSame('waba-100', $company->meta_waba_id);
        $this->assertSame('phone-100', $company->meta_phone_number_id);
        $this->assertContains(
            'WhatsApp connected. Meta subscription verification is pending; run diagnostics shortly.',
            $result['warnings']
        );
        $this->assertDatabaseHas('whatsapp_connect_sessions', [
            'state' => $state,
            'status' => 'completed',
        ]);

        $requestCount = count(Http::recorded());
        $second = $service->complete($company, $user->id, $payload);
        $this->assertTrue($second['idempotent']);
        $this->assertCount($requestCount, Http::recorded());
        $this->assertSame(1, Company::query()->where('meta_phone_number_id', 'phone-100')->count());
    }

    public function test_fresh_session_recovers_a_prior_subscription_failure_without_duplicate_tenant_assets(): void
    {
        [$company, $user] = $this->tenant();
        $service = app(MetaEmbeddedSignupService::class);
        $failedState = $service->createState($company->id, $user->id, MetaEmbeddedSignupService::MODE_BUSINESS_APP);
        \Illuminate\Support\Facades\DB::table('whatsapp_connect_sessions')
            ->where('state', $failedState)
            ->update([
                'status' => 'failed',
                'error_code' => 'subscription_not_confirmed',
                'error_message' => 'Meta did not confirm the WhatsApp webhook subscription.',
            ]);

        $this->fakeMetaCompletion(isOnBusinessApp: true, subscriptionReadback: ['data' => []]);
        $recoveryState = $service->createState($company->id, $user->id, MetaEmbeddedSignupService::MODE_BUSINESS_APP);
        $service->complete($company, $user->id, $this->completionPayload($recoveryState));

        $this->assertDatabaseHas('whatsapp_connect_sessions', [
            'state' => $failedState,
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('whatsapp_connect_sessions', [
            'state' => $recoveryState,
            'status' => 'completed',
        ]);
        $this->assertSame(1, Company::query()->where('meta_waba_id', 'waba-100')->count());
        $this->assertSame(1, Company::query()->where('meta_phone_number_id', 'phone-100')->count());
    }

    public function test_subscription_readback_failure_is_audited_safely_without_rolling_back_connection(): void
    {
        [$company, $user] = $this->tenant();
        $this->fakeMetaCompletion(
            isOnBusinessApp: true,
            subscriptionReadback: [
                'error' => [
                    'code' => 4,
                    'type' => 'OAuthException',
                    'message' => 'Temporary failure for +971 50 123 4567 using EA'.str_repeat('A', 32),
                ],
            ],
            subscriptionReadbackStatus: 503
        );
        $service = app(MetaEmbeddedSignupService::class);
        $state = $service->createState($company->id, $user->id, MetaEmbeddedSignupService::MODE_BUSINESS_APP);

        $result = $service->complete($company, $user->id, $this->completionPayload($state));

        $company->refresh();
        $this->assertSame('pending_verification', $company->whatsapp_webhook_subscription_status);
        $this->assertNotNull($company->meta_access_token);
        $this->assertNotEmpty($result['warnings']);

        $audit = \App\Models\WhatsApp\WhatsAppConnectionAudit::query()
            ->where('company_id', $company->id)
            ->where('event', 'signup_completed')
            ->latest('id')
            ->firstOrFail();
        $serialized = json_encode($audit->context);
        $this->assertSame(503, data_get($audit->context, 'subscription_verification.http_status'));
        $this->assertSame(4, data_get($audit->context, 'subscription_verification.meta_error_code'));
        $this->assertStringContainsString('[redacted identifier]', (string) $serialized);
        $this->assertStringContainsString('[redacted credential]', (string) $serialized);
        $this->assertStringNotContainsString('+971 50 123 4567', (string) $serialized);
        $this->assertStringNotContainsString('EA'.str_repeat('A', 32), (string) $serialized);
    }

    public function test_failed_subscription_post_records_sanitized_provider_status_and_does_not_persist_assets(): void
    {
        [$company, $user] = $this->tenant();
        $this->fakeMetaCompletion(
            isOnBusinessApp: true,
            subscriptionPost: [
                'error' => [
                    'code' => 200,
                    'type' => 'OAuthException',
                    'message' => 'Permission failed for 123456789012345 and token EA'.str_repeat('B', 32),
                ],
            ],
            subscriptionPostStatus: 403
        );
        $service = app(MetaEmbeddedSignupService::class);
        $state = $service->createState($company->id, $user->id, MetaEmbeddedSignupService::MODE_BUSINESS_APP);

        try {
            $service->complete($company, $user->id, $this->completionPayload($state));
            $this->fail('Expected subscription failure.');
        } catch (WhatsAppOnboardingException $exception) {
            $this->assertSame('subscription_failed', $exception->reason);
            $this->assertSame(403, $exception->safeContext['http_status']);
            $this->assertSame(200, $exception->safeContext['meta_error_code']);
        }

        $session = \Illuminate\Support\Facades\DB::table('whatsapp_connect_sessions')
            ->where('state', $state)
            ->first();
        $serialized = (string) $session->payload;
        $this->assertSame('failed', $session->status);
        $this->assertStringContainsString('[redacted identifier]', $serialized);
        $this->assertStringContainsString('[redacted credential]', $serialized);
        $this->assertStringNotContainsString('123456789012345', $serialized);
        $this->assertStringNotContainsString('EA'.str_repeat('B', 32), $serialized);
        $this->assertNull($company->fresh()->meta_waba_id);
        $this->assertNull($company->fresh()->meta_access_token);
    }

    public function test_standard_cloud_api_flow_remains_separate_and_operational(): void
    {
        [$company, $user] = $this->tenant();
        $this->fakeMetaCompletion(isOnBusinessApp: false);
        $service = app(MetaEmbeddedSignupService::class);
        $state = $service->createState($company->id, $user->id, MetaEmbeddedSignupService::MODE_CLOUD_API);
        $payload = array_merge($this->completionPayload($state), ['session_event' => 'FINISH']);

        $service->complete($company, $user->id, $payload);

        $company->refresh();
        $this->assertSame('cloud_api', $company->whatsapp_connection_mode);
        $this->assertFalse($company->whatsapp_coexistence_enabled);
        Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/smb_app_data'));
    }

    public function test_callback_validation_rejects_malformed_session_assets_without_meta_calls(): void
    {
        [, $user] = $this->tenant();
        Http::fake();

        $this->actingAs($user)->postJson(route('admin.whatsapp.connect.callback'), [
            'code' => 'one-time-code',
            'state' => 'too-short',
            'session_event' => MetaEmbeddedSignupService::EVENT_BUSINESS_APP_FINISH,
            'waba_id' => 'not-a-meta-id',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['state', 'waba_id']);

        Http::assertNothingSent();
    }

    public function test_authorization_code_and_tokens_are_absent_from_logs_and_failure_response(): void
    {
        [$company, $user] = $this->tenant();
        $records = [];
        \Illuminate\Support\Facades\Log::listen(function (MessageLogged $event) use (&$records) {
            $records[] = ['message' => $event->message, 'context' => $event->context];
        });
        Http::fake();
        $state = app(MetaEmbeddedSignupService::class)->createState(
            $company->id,
            $user->id,
            MetaEmbeddedSignupService::MODE_BUSINESS_APP
        );

        $response = $this->actingAs($user)->postJson(route('admin.whatsapp.connect.callback'), [
            'code' => 'private-one-time-code',
            'state' => $state,
            'session_event' => 'FINISH',
            'waba_id' => '100200300',
            'phone_number_id' => '400500600',
        ])->assertUnprocessable();

        $serialized = json_encode(['response' => $response->json(), 'logs' => $records]);
        $this->assertStringNotContainsString('private-one-time-code', (string) $serialized);
        $this->assertStringNotContainsString('short-lived-token', (string) $serialized);
        Http::assertNothingSent();
    }

    private function fakeMetaCompletion(
        bool $isOnBusinessApp,
        array $subscriptionReadback = ['data' => [['id' => '925717083333434']]],
        int $subscriptionReadbackStatus = 200,
        array $subscriptionPost = ['success' => true],
        int $subscriptionPostStatus = 200,
    ): void
    {
        Http::fake(function (Request $request) use (
            $isOnBusinessApp,
            $subscriptionReadback,
            $subscriptionReadbackStatus,
            $subscriptionPost,
            $subscriptionPostStatus
        ) {
            $url = $request->url();

            return match (true) {
                str_contains($url, '/oauth/access_token') => Http::response([
                    'access_token' => 'short-lived-token', 'token_type' => 'bearer', 'expires_in' => 3600,
                ]),
                str_contains($url, '/debug_token') => Http::response(['data' => [
                    'is_valid' => true,
                    'app_id' => '925717083333434',
                    'scopes' => ['whatsapp_business_management', 'whatsapp_business_messaging'],
                    'granular_scopes' => [[
                        'scope' => 'whatsapp_business_management', 'target_ids' => ['waba-100'],
                    ]],
                ]]),
                str_contains($url, '/waba-100/phone_numbers') => Http::response(['data' => [[
                    'id' => 'phone-100', 'display_phone_number' => '+971 50 000 0000',
                    'status' => 'CONNECTED', 'is_on_biz_app' => $isOnBusinessApp,
                ]]]),
                str_contains($url, '/phone-100/smb_app_data') => Http::response(['success' => true, 'data' => ['request_id' => 'request-1']]),
                str_contains($url, '/phone-100') => Http::response([
                    'id' => 'phone-100', 'display_phone_number' => '+971 50 000 0000',
                    'status' => 'CONNECTED', 'is_on_biz_app' => $isOnBusinessApp,
                ]),
                $request->method() === 'POST' && str_contains($url, '/waba-100/subscribed_apps') => Http::response(
                    $subscriptionPost,
                    $subscriptionPostStatus
                ),
                $request->method() === 'GET' && str_contains($url, '/waba-100/subscribed_apps') => Http::response(
                    $subscriptionReadback,
                    $subscriptionReadbackStatus
                ),
                default => Http::response([], 404),
            };
        });
    }

    private function completionPayload(string $state): array
    {
        return [
            'state' => $state,
            'code' => 'one-time-authorization-code',
            'session_event' => MetaEmbeddedSignupService::EVENT_BUSINESS_APP_FINISH,
            'waba_id' => 'waba-100',
            'phone_number_id' => 'phone-100',
            'business_id' => null,
        ];
    }

    private function tenant(): array
    {
        $company = Company::query()->create(['name' => 'Coexistence Test Garage']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'admin',
            'status' => true,
            'must_change_password' => false,
        ]);

        return [$company, $user];
    }
}
