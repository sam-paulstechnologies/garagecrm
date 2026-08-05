<?php

namespace App\Services\WhatsApp;

use App\Exceptions\WhatsAppOnboardingException;
use App\Models\MessageLog;
use App\Models\System\Company;
use App\Models\WhatsApp\WhatsAppConnectionAudit;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MetaEmbeddedSignupService
{
    public const MODE_BUSINESS_APP = 'business_app_onboarding';

    public const MODE_CLOUD_API = 'cloud_api';

    public const EVENT_BUSINESS_APP_FINISH = 'FINISH_WHATSAPP_BUSINESS_APP_ONBOARDING';

    private string $graphBase;

    private string $graphVersion;

    private ?string $appId;

    private ?string $appSecret;

    public function __construct()
    {
        $this->graphBase = rtrim((string) config('services.meta.graph_base', 'https://graph.facebook.com'), '/');
        $this->graphVersion = trim((string) config('services.meta.api_version', 'v25.0'), '/');
        $this->appId = config('services.meta.app_id');
        $this->appSecret = config('services.meta.app_secret');
    }

    public function signupConfiguration(string $connectionMode): array
    {
        $mode = $this->normalizeConnectionMode($connectionMode);
        $settings = (array) config('services.meta.whatsapp_embedded_signup', []);
        $configId = $mode === self::MODE_BUSINESS_APP
            ? ($settings['business_app_config_id'] ?? null)
            : ($settings['cloud_api_config_id'] ?? config('services.meta.whatsapp_embedded_signup_config_id'));

        $extras = [
            'setup' => (object) [],
            'version' => (string) ($settings['version'] ?? 'v4'),
            'sessionInfoVersion' => (string) ($settings['session_info_version'] ?? '3'),
        ];

        if ($mode === self::MODE_BUSINESS_APP) {
            $extras['featureType'] = (string) ($settings['business_app_feature_type'] ?? self::MODE_BUSINESS_APP);
        }

        return [
            'app_id' => $this->appId,
            'config_id' => $configId,
            'graph_version' => $this->graphVersion,
            'connection_mode' => $mode,
            'extras' => $extras,
            'is_configured' => filled($this->appId) && filled($this->appSecret) && filled($configId),
        ];
    }

    public function createState(int $companyId, ?int $userId, string $connectionMode): string
    {
        if (! Schema::hasTable('whatsapp_connect_sessions')) {
            throw new WhatsAppOnboardingException(
                'connect_sessions_missing',
                'WhatsApp onboarding storage is not ready. Please contact support.'
            );
        }

        $mode = $this->normalizeConnectionMode($connectionMode);
        $state = Str::random(64);
        $now = now();
        $ttl = max(5, (int) config('services.meta.whatsapp_embedded_signup.session_ttl_minutes', 15));

        DB::table('whatsapp_connect_sessions')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'state' => $state,
            'connection_mode' => $mode,
            'status' => 'started',
            'payload' => json_encode([
                'started_from' => 'embedded_signup',
                'embedded_signup_version' => config('services.meta.whatsapp_embedded_signup.version', 'v4'),
            ]),
            'started_at' => $now,
            'expires_at' => $now->copy()->addMinutes($ttl),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->audit($companyId, $userId, 'signup_started', 'started', $mode);

        return $state;
    }

    public function complete(Company $company, int $userId, array $input): array
    {
        $session = $this->beginCompletion(
            (string) $input['state'],
            (int) $company->id,
            $userId
        );

        if (($session->status ?? null) === 'completed') {
            return [
                'company' => $company->fresh(),
                'status' => $this->connectionStatus($company->fresh()),
                'idempotent' => true,
                'warnings' => [],
            ];
        }

        $mode = $this->normalizeConnectionMode($session->connection_mode ?? null);
        $sessionEvent = (string) ($input['session_event'] ?? '');

        try {
            $this->assertExpectedSessionEvent($mode, $sessionEvent);
            $tokenPayload = $this->exchangeCodeForAccessToken((string) $input['code']);
            $accessToken = (string) $tokenPayload['access_token'];
            $tokenInspection = $this->inspectAccessToken($accessToken);

            $wabaId = $this->resolveWabaId(
                $input['waba_id'] ?? null,
                $tokenInspection
            );
            $businessId = $this->validateBusinessId($input['business_id'] ?? null, $accessToken);
            $phone = $this->resolveAndValidatePhoneNumber(
                $wabaId,
                $input['phone_number_id'] ?? null,
                $accessToken,
                $mode
            );

            $this->assertTenantAssetIsAvailable($company, (string) $phone['id']);
            $subscription = $this->subscribeAppToWaba($wabaId, $accessToken);

            $encryptedToken = Crypt::encryptString($accessToken);

            $company = DB::transaction(function () use (
                $company,
                $userId,
                $session,
                $sessionEvent,
                $mode,
                $tokenPayload,
                $encryptedToken,
                $wabaId,
                $businessId,
                $phone,
                $subscription
            ): Company {
                $lockedCompany = Company::query()->lockForUpdate()->findOrFail($company->id);

                $lockedCompany->forceFill([
                    'meta_phone_number_id' => (string) $phone['id'],
                    'meta_access_token' => $encryptedToken,
                    'meta_waba_id' => $wabaId,
                    'meta_business_id' => $businessId,
                    'meta_display_phone_number' => $phone['display_phone_number'] ?? null,
                    'meta_token_expires_at' => $this->resolveTokenExpiry($tokenPayload),
                    'is_whatsapp_active' => true,
                    'whatsapp_connection_mode' => $mode,
                    'whatsapp_coexistence_enabled' => $mode === self::MODE_BUSINESS_APP,
                    'whatsapp_coexistence_status' => $mode === self::MODE_BUSINESS_APP ? 'connected' : null,
                    'whatsapp_onboarding_source' => $mode === self::MODE_BUSINESS_APP
                        ? 'embedded_signup_business_app_onboarding'
                        : 'embedded_signup_cloud_api',
                    'whatsapp_connected_at' => now(),
                    'whatsapp_webhook_subscription_status' => $subscription['status'],
                    'whatsapp_webhook_subscription_checked_at' => now(),
                ])->save();

                DB::table('whatsapp_connect_sessions')
                    ->where('id', $session->id)
                    ->where('company_id', $lockedCompany->id)
                    ->update([
                        'status' => 'completed',
                        'session_event' => $sessionEvent,
                        'meta_business_id' => $businessId,
                        'waba_id' => $wabaId,
                        'phone_number_id' => (string) $phone['id'],
                        'display_phone_number' => $phone['display_phone_number'] ?? null,
                        'payload' => json_encode([
                            'connection_mode' => $mode,
                            'token_type' => $tokenPayload['token_type'] ?? null,
                            'has_expiry' => filled($tokenPayload['expires_in'] ?? null),
                            'webhook_subscription' => $subscription['status'],
                            'webhook_subscription_check' => $subscription['verification'],
                        ]),
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);

                $this->audit(
                    (int) $lockedCompany->id,
                    $userId,
                    'signup_completed',
                    'success',
                    $mode,
                    $wabaId,
                    (string) $phone['id'],
                    [
                        'session_event' => $sessionEvent,
                        'subscription' => $subscription['status'],
                        'subscription_verification' => $subscription['verification'],
                    ]
                );

                return $lockedCompany->fresh();
            });

            $warnings = [];

            if ($subscription['status'] !== 'subscribed') {
                $warnings[] = 'WhatsApp connected. Meta subscription verification is pending; run diagnostics shortly.';
            }

            if ($mode === self::MODE_BUSINESS_APP) {
                try {
                    $this->requestSync($company, 'smb_app_state_sync', $userId);
                } catch (WhatsAppOnboardingException $exception) {
                    $warnings[] = 'WhatsApp connected, but contact synchronization must be retried from diagnostics.';
                }
            }

            return [
                'company' => $company,
                'status' => $this->connectionStatus($company),
                'idempotent' => false,
                'warnings' => $warnings,
            ];
        } catch (\Throwable $exception) {
            $safe = $exception instanceof WhatsAppOnboardingException
                ? $exception
                : new WhatsAppOnboardingException(
                    'completion_failed',
                    'Meta could not complete the WhatsApp connection. Restart Embedded Signup and try again.',
                    previous: $exception
                );

            $this->markSessionFailed((int) $session->id, $safe);
            $this->audit(
                (int) $company->id,
                $userId,
                'signup_failed',
                'failed',
                $mode,
                context: array_filter([
                    'reason' => $safe->reason,
                    'provider' => $safe->safeContext ?: null,
                ], fn ($value) => $value !== null)
            );

            throw $safe;
        }
    }

    public function exchangeCodeForAccessToken(string $code): array
    {
        $this->assertAppCredentials();

        $response = Http::timeout(30)->acceptJson()->get($this->graphUrl('oauth/access_token'), [
            'client_id' => $this->appId,
            'client_secret' => $this->appSecret,
            'code' => $code,
        ]);

        $data = $this->successfulJson($response, 'code_exchange_failed', 'Meta rejected or expired the authorization code. Restart Embedded Signup.');

        if (blank($data['access_token'] ?? null)) {
            throw new WhatsAppOnboardingException('missing_access_token', 'Meta did not return a usable access token. Restart Embedded Signup.');
        }

        return $data;
    }

    public function inspectAccessToken(string $accessToken): array
    {
        $this->assertAppCredentials();

        $response = Http::timeout(30)->acceptJson()->get($this->graphUrl('debug_token'), [
            'input_token' => $accessToken,
            'access_token' => $this->appId.'|'.$this->appSecret,
        ]);

        $payload = $this->successfulJson($response, 'token_inspection_failed', 'Meta could not validate the granted access. Restart Embedded Signup.');
        $data = (array) ($payload['data'] ?? []);

        if (! ($data['is_valid'] ?? false) || (string) ($data['app_id'] ?? '') !== (string) $this->appId) {
            throw new WhatsAppOnboardingException('invalid_access_token', 'The Meta authorization is not valid for SayaraForce.');
        }

        $scopes = array_map('strval', (array) ($data['scopes'] ?? []));
        foreach (['whatsapp_business_management', 'whatsapp_business_messaging'] as $requiredScope) {
            if (! in_array($requiredScope, $scopes, true)) {
                throw new WhatsAppOnboardingException('missing_scope', 'Meta did not grant the required WhatsApp permissions.');
            }
        }

        return $data;
    }

    public function fetchPhoneNumbers(string $wabaId, string $accessToken): array
    {
        $response = Http::timeout(30)
            ->withToken($accessToken)
            ->acceptJson()
            ->get($this->graphUrl($wabaId.'/phone_numbers'), [
                'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status,status,platform_type,is_on_biz_app',
                'limit' => 100,
            ]);

        $payload = $this->successfulJson($response, 'phone_discovery_failed', 'Meta could not verify the selected WhatsApp number.');

        return array_values(array_filter((array) ($payload['data'] ?? []), 'is_array'));
    }

    public function fetchPhoneNumber(string $phoneNumberId, string $accessToken): array
    {
        $response = Http::timeout(30)
            ->withToken($accessToken)
            ->acceptJson()
            ->get($this->graphUrl($phoneNumberId), [
                'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status,status,platform_type,is_on_biz_app',
            ]);

        return $this->successfulJson($response, 'phone_validation_failed', 'Meta could not validate the WhatsApp number.');
    }

    public function subscribeAppToWaba(string $wabaId, string $accessToken): array
    {
        $response = Http::timeout(30)
            ->withToken($accessToken)
            ->acceptJson()
            ->post($this->graphUrl($wabaId.'/subscribed_apps'));

        $data = $this->successfulJson($response, 'subscription_failed', 'The WhatsApp account was validated, but webhook subscription failed.');

        if (! filter_var($data['success'] ?? false, FILTER_VALIDATE_BOOL)) {
            throw new WhatsAppOnboardingException(
                'subscription_not_confirmed',
                'Meta did not confirm the WhatsApp webhook subscription.',
                safeContext: $this->metaResponseContext(
                    $response,
                    'Meta returned a successful response without confirming the subscription.'
                )
            );
        }

        $verification = $this->readAppSubscription($wabaId, $accessToken);

        return [
            'status' => ($verification['readable'] && $verification['subscribed'])
                ? 'subscribed'
                : 'pending_verification',
            'post' => $this->metaResponseContext(
                $response,
                'Meta accepted the WABA app subscription request.'
            ),
            'verification' => $verification['context'],
        ];
    }

    public function repairWabaSubscription(
        Company $company,
        ?int $userId = null,
        int $maxAttempts = 4,
        int $backoffMilliseconds = 750
    ): array {
        $wabaId = trim((string) $company->meta_waba_id);
        if ($wabaId === '') {
            throw new WhatsAppOnboardingException(
                'missing_waba',
                'The connected WhatsApp Business Account is unavailable.'
            );
        }

        $accessToken = $this->decryptStoredAccessToken($company);

        try {
            $postResponse = Http::timeout(30)
                ->withToken($accessToken)
                ->acceptJson()
                ->post($this->graphUrl($wabaId.'/subscribed_apps'));

            $postData = $this->successfulJson(
                $postResponse,
                'subscription_failed',
                'Meta did not accept the WABA app subscription request.'
            );

            if (! filter_var($postData['success'] ?? false, FILTER_VALIDATE_BOOL)) {
                throw new WhatsAppOnboardingException(
                    'subscription_not_confirmed',
                    'Meta did not confirm the WABA app subscription request.',
                    safeContext: $this->metaResponseContext(
                        $postResponse,
                        'Meta returned a successful response without confirming the subscription.'
                    )
                );
            }

            $verification = $this->readAppSubscriptionWithRetry(
                $wabaId,
                $accessToken,
                $maxAttempts,
                $backoffMilliseconds
            );
            $confirmed = $verification['readable'] && $verification['subscribed'];

            $company->forceFill([
                'whatsapp_webhook_subscription_status' => $confirmed
                    ? 'subscribed'
                    : 'pending_verification',
                'whatsapp_webhook_subscription_checked_at' => now(),
            ])->save();

            $result = [
                'status' => $confirmed ? 'subscribed' : 'pending_verification',
                'post' => $this->metaResponseContext(
                    $postResponse,
                    'Meta accepted the WABA app subscription request.'
                ),
                'verification' => $verification['context'],
                'attempts' => $verification['attempts'],
            ];

            $this->audit(
                (int) $company->id,
                $userId,
                'waba_subscription_repaired',
                $confirmed ? 'success' : 'warning',
                $this->normalizeConnectionMode($company->whatsapp_connection_mode ?? null),
                $company->meta_waba_id,
                $company->meta_phone_number_id,
                $result
            );

            return $result;
        } catch (WhatsAppOnboardingException $exception) {
            $this->audit(
                (int) $company->id,
                $userId,
                'waba_subscription_repair_failed',
                'failed',
                $this->normalizeConnectionMode($company->whatsapp_connection_mode ?? null),
                $company->meta_waba_id,
                $company->meta_phone_number_id,
                [
                    'reason' => $exception->reason,
                    'provider' => $exception->safeContext,
                ]
            );

            throw $exception;
        }
    }

    public function requestSync(Company $company, string $syncType, ?int $userId = null): array
    {
        if (! in_array($syncType, ['smb_app_state_sync', 'history'], true)) {
            throw new WhatsAppOnboardingException('invalid_sync_type', 'Unsupported WhatsApp synchronization request.');
        }

        if ($this->normalizeConnectionMode($company->whatsapp_connection_mode ?? null) !== self::MODE_BUSINESS_APP) {
            throw new WhatsAppOnboardingException('sync_not_available', 'Contact and history sync are available only for an existing WhatsApp Business app connection.');
        }

        $phoneNumberId = (string) ($company->meta_phone_number_id ?? '');
        $accessToken = $this->decryptStoredAccessToken($company);

        if ($phoneNumberId === '') {
            throw new WhatsAppOnboardingException('missing_phone', 'The connected WhatsApp phone number is unavailable.');
        }

        $response = Http::timeout(30)
            ->withToken($accessToken)
            ->acceptJson()
            ->post($this->graphUrl($phoneNumberId.'/smb_app_data'), [
                'messaging_product' => 'whatsapp',
                'sync_type' => $syncType,
            ]);

        $payload = $this->successfulJson($response, 'sync_request_failed', 'Meta did not accept the synchronization request.');
        $prefix = $syncType === 'history' ? 'whatsapp_history_sync' : 'whatsapp_contact_sync';

        $company->forceFill([
            $prefix.'_status' => 'requested',
            $prefix.'_requested_at' => now(),
        ])->save();

        $this->audit(
            (int) $company->id,
            $userId,
            $syncType === 'history' ? 'history_sync_requested' : 'contact_sync_requested',
            'requested',
            self::MODE_BUSINESS_APP,
            $company->meta_waba_id,
            $company->meta_phone_number_id,
            ['request_id_present' => filled(data_get($payload, 'data.request_id'))]
        );

        return ['accepted' => true, 'request_id_present' => filled(data_get($payload, 'data.request_id'))];
    }

    public function diagnostics(Company $company, ?int $userId = null): array
    {
        $callbackVerification = $this->callbackVerificationStatus();
        $wabaId = trim((string) $company->meta_waba_id);

        if ($wabaId === '') {
            $company->forceFill([
                'whatsapp_webhook_subscription_status' => 'pending_onboarding',
                'whatsapp_webhook_subscription_checked_at' => null,
            ])->save();

            $this->audit(
                (int) $company->id,
                $userId,
                'diagnostics_checked',
                'info',
                $this->normalizeConnectionMode($company->whatsapp_connection_mode ?? null),
                null,
                $company->meta_phone_number_id,
                [
                    'callback_verification' => $callbackVerification,
                    'subscription' => 'pending_onboarding',
                ]
            );

            return [
                'callback_verification' => $callbackVerification,
                'phone_status' => 'not_checked',
                'quality_rating' => 'not_checked',
                'is_on_business_app' => null,
                'webhook_subscription' => 'pending_onboarding',
                'message' => 'WABA subscription will be verified after onboarding',
            ];
        }

        $accessToken = $this->decryptStoredAccessToken($company);
        $phoneNumberId = trim((string) $company->meta_phone_number_id);
        $phone = $phoneNumberId !== ''
            ? $this->fetchPhoneNumber($phoneNumberId, $accessToken)
            : [];
        $subscribed = $this->isAppSubscribedToWaba($wabaId, $accessToken);

        $company->forceFill([
            'whatsapp_webhook_subscription_status' => $subscribed ? 'subscribed' : 'missing',
            'whatsapp_webhook_subscription_checked_at' => now(),
        ])->save();

        $this->audit(
            (int) $company->id,
            $userId,
            'diagnostics_checked',
            $subscribed ? 'success' : 'warning',
            $this->normalizeConnectionMode($company->whatsapp_connection_mode ?? null),
            $company->meta_waba_id,
            $company->meta_phone_number_id,
            [
                'callback_verification' => $callbackVerification,
                'subscription' => $subscribed ? 'subscribed' : 'missing',
            ]
        );

        return [
            'callback_verification' => $callbackVerification,
            'phone_status' => $phone['status'] ?? 'unknown',
            'quality_rating' => $phone['quality_rating'] ?? 'unknown',
            'is_on_business_app' => $this->asBoolean($phone['is_on_biz_app'] ?? null),
            'webhook_subscription' => $subscribed ? 'subscribed' : 'missing',
            'message' => $subscribed
                ? 'The WABA app subscription is active.'
                : 'The WABA app subscription is missing.',
        ];
    }

    public function disconnectCompany(Company $company, ?int $userId = null): Company
    {
        $mode = $this->normalizeConnectionMode($company->whatsapp_connection_mode ?? null);

        $company->forceFill([
            'is_whatsapp_active' => false,
            'meta_access_token' => null,
            'meta_token_expires_at' => null,
            'whatsapp_connection_mode' => 'manual',
            'whatsapp_coexistence_enabled' => false,
            'whatsapp_coexistence_status' => 'disconnected',
            'whatsapp_webhook_subscription_status' => 'unknown',
        ])->save();

        $this->audit((int) $company->id, $userId, 'connection_disabled_locally', 'success', $mode);

        return $company->fresh();
    }

    public function connectionStatus(Company $company): array
    {
        $mode = $this->normalizeConnectionMode($company->whatsapp_connection_mode ?? null, allowManual: true);
        $callbackVerification = $this->callbackVerificationStatus();
        $wabaSubscription = blank($company->meta_waba_id)
            ? 'WABA subscription will be verified after onboarding'
            : match ((string) $company->whatsapp_webhook_subscription_status) {
                'subscribed' => 'Subscribed',
                'pending_verification' => 'Pending verification',
                'missing' => 'Not subscribed',
                default => 'Not checked',
            };
        $lastApiOutbound = null;
        if (Schema::hasTable('message_logs')) {
            $lastApiQuery = MessageLog::query()
                ->where('company_id', $company->id)
                ->where('direction', 'out');

            if (Schema::hasColumn('message_logs', 'source')) {
                $lastApiQuery->where(function ($query) {
                    $query->whereNull('source')->orWhere('source', '!=', 'whatsapp_business_app');
                });
            }

            $lastApiOutbound = $lastApiQuery->max('created_at');
        }

        return [
            'is_connected' => filled($company->meta_phone_number_id)
                && filled($company->meta_access_token)
                && (bool) $company->is_whatsapp_active,
            'is_active' => (bool) $company->is_whatsapp_active,
            'waba_id' => $company->meta_waba_id,
            'masked_phone_number_id' => $this->maskIdentifier($company->meta_phone_number_id),
            'business_id' => $company->meta_business_id,
            'masked_display_phone_number' => $this->maskPhone($company->meta_display_phone_number),
            'token_expires_at' => $company->meta_token_expires_at,
            'connection_mode' => $mode,
            'business_app_onboarding' => $mode === self::MODE_BUSINESS_APP,
            'connection_status' => $company->whatsapp_coexistence_status,
            'onboarding_source' => $company->whatsapp_onboarding_source,
            'connected_at' => $company->whatsapp_connected_at,
            'callback_verification_status' => $callbackVerification === 'ready'
                ? 'Ready for Meta verification'
                : 'Verification token is not configured',
            'waba_subscription_status' => $wabaSubscription,
            'webhook_subscription_status' => $company->whatsapp_webhook_subscription_status,
            'webhook_subscription_checked_at' => $company->whatsapp_webhook_subscription_checked_at,
            'last_webhook_at' => $company->whatsapp_last_webhook_at,
            'last_inbound_at' => $company->whatsapp_last_inbound_at,
            'last_mobile_app_echo_at' => $company->whatsapp_last_echo_at,
            'last_api_outbound_at' => $lastApiOutbound,
            'contact_sync_status' => $company->whatsapp_contact_sync_status,
            'history_sync_status' => $company->whatsapp_history_sync_status,
        ];
    }

    private function callbackVerificationStatus(): string
    {
        return filled(
            config('services.meta.whatsapp_verify_token')
            ?: config('services.whatsapp.meta.verify_token')
        ) ? 'ready' : 'not_configured';
    }

    private function isAppSubscribedToWaba(string $wabaId, string $accessToken): bool
    {
        $verification = $this->readAppSubscription($wabaId, $accessToken);

        if (! $verification['readable']) {
            throw new WhatsAppOnboardingException(
                'subscription_check_failed',
                'Meta could not verify the WABA app subscription.',
                safeContext: $verification['context']
            );
        }

        return $verification['subscribed'];
    }

    private function readAppSubscription(string $wabaId, string $accessToken): array
    {
        $response = Http::timeout(30)
            ->withToken($accessToken)
            ->acceptJson()
            ->get($this->graphUrl($wabaId.'/subscribed_apps'));

        if (! $response->successful() || ! is_array($response->json())) {
            return [
                'readable' => false,
                'subscribed' => false,
                'context' => $this->metaResponseContext(
                    $response,
                    'Meta did not return a readable subscription status.'
                ),
            ];
        }

        $subscriptions = $response->json();
        $subscribed = collect((array) ($subscriptions['data'] ?? []))
            ->contains(function ($app): bool {
                if (! is_array($app)) {
                    return false;
                }

                $subscribedAppId = data_get($app, 'whatsapp_business_api_data.id')
                    ?? ($app['id'] ?? null);

                return (string) $subscribedAppId === (string) $this->appId;
            });

        return [
            'readable' => true,
            'subscribed' => $subscribed,
            'context' => $this->metaResponseContext(
                $response,
                $subscribed
                    ? 'Meta confirmed the SayaraForce app subscription.'
                    : 'Meta accepted the subscription but it is not yet visible in the read-back response.'
            ),
        ];
    }

    private function readAppSubscriptionWithRetry(
        string $wabaId,
        string $accessToken,
        int $maxAttempts,
        int $backoffMilliseconds
    ): array {
        $maxAttempts = min(max($maxAttempts, 1), 5);
        $backoffMilliseconds = min(max($backoffMilliseconds, 0), 5000);
        $verification = [];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $verification = $this->readAppSubscription($wabaId, $accessToken);
            $verification['attempts'] = $attempt;

            if ($verification['readable'] && $verification['subscribed']) {
                return $verification;
            }

            if ($attempt < $maxAttempts && $backoffMilliseconds > 0) {
                usleep($backoffMilliseconds * $attempt * 1000);
            }
        }

        return $verification;
    }

    public function normalizeConnectionMode(?string $mode, bool $allowManual = false): string
    {
        $mode = strtolower(trim((string) $mode));

        if ($mode === 'coexistence') {
            return self::MODE_BUSINESS_APP;
        }

        if (in_array($mode, [self::MODE_BUSINESS_APP, self::MODE_CLOUD_API], true)) {
            return $mode;
        }

        if ($allowManual && $mode === 'manual') {
            return 'manual';
        }

        return self::MODE_BUSINESS_APP;
    }

    private function beginCompletion(string $state, int $companyId, int $userId): object
    {
        return DB::transaction(function () use ($state, $companyId, $userId): object {
            $session = DB::table('whatsapp_connect_sessions')
                ->where('state', $state)
                ->lockForUpdate()
                ->first();

            if (! $session || (int) $session->company_id !== $companyId || (int) ($session->user_id ?? 0) !== $userId) {
                throw new WhatsAppOnboardingException('invalid_state', 'This WhatsApp onboarding session is invalid. Restart the connection flow.');
            }

            if (($session->status ?? null) === 'completed') {
                return $session;
            }

            if (($session->status ?? null) !== 'started') {
                throw new WhatsAppOnboardingException('session_not_available', 'This WhatsApp onboarding session has already been used. Restart the connection flow.');
            }

            if (filled($session->expires_at ?? null) && now()->greaterThan(Carbon::parse($session->expires_at))) {
                DB::table('whatsapp_connect_sessions')->where('id', $session->id)->update([
                    'status' => 'expired',
                    'error_code' => 'session_expired',
                    'error_message' => 'The onboarding session expired.',
                    'updated_at' => now(),
                ]);

                throw new WhatsAppOnboardingException('session_expired', 'This WhatsApp onboarding session expired. Restart the connection flow.');
            }

            DB::table('whatsapp_connect_sessions')->where('id', $session->id)->update([
                'status' => 'processing',
                'processing_started_at' => now(),
                'updated_at' => now(),
            ]);

            $session->status = 'processing';

            return $session;
        });
    }

    private function assertExpectedSessionEvent(string $mode, string $sessionEvent): void
    {
        if ($mode === self::MODE_BUSINESS_APP && $sessionEvent !== self::EVENT_BUSINESS_APP_FINISH) {
            throw new WhatsAppOnboardingException(
                'wrong_business_app_flow',
                'Meta did not confirm the WhatsApp Business App onboarding path. Nothing was connected; restart and cancel if Meta asks to migrate the number.'
            );
        }

        if ($mode === self::MODE_CLOUD_API && ! in_array($sessionEvent, ['FINISH', 'FINISH_ONLY_WABA'], true)) {
            throw new WhatsAppOnboardingException('wrong_cloud_api_flow', 'Meta did not complete the dedicated Cloud API onboarding path.');
        }
    }

    private function resolveWabaId(?string $suppliedWabaId, array $tokenInspection): string
    {
        $targetIds = collect((array) ($tokenInspection['granular_scopes'] ?? []))
            ->filter(fn ($scope) => is_array($scope) && ($scope['scope'] ?? null) === 'whatsapp_business_management')
            ->flatMap(fn ($scope) => (array) ($scope['target_ids'] ?? []))
            ->map(fn ($id) => (string) $id)
            ->filter()
            ->unique()
            ->values();

        $wabaId = trim((string) $suppliedWabaId);

        if ($wabaId === '' && $targetIds->count() === 1) {
            $wabaId = (string) $targetIds->first();
        }

        if ($wabaId === '') {
            throw new WhatsAppOnboardingException('missing_waba', 'Meta did not return a single WhatsApp Business Account. Restart Embedded Signup.');
        }

        if ($targetIds->isNotEmpty() && ! $targetIds->contains($wabaId)) {
            throw new WhatsAppOnboardingException('waba_not_granted', 'The selected WhatsApp Business Account was not granted to SayaraForce.');
        }

        return $wabaId;
    }

    private function validateBusinessId(?string $businessId, string $accessToken): ?string
    {
        $businessId = trim((string) $businessId);

        if ($businessId === '') {
            return null;
        }

        $response = Http::timeout(30)
            ->withToken($accessToken)
            ->acceptJson()
            ->get($this->graphUrl($businessId), ['fields' => 'id']);

        $data = $this->successfulJson($response, 'business_validation_failed', 'Meta could not validate the selected business portfolio.');

        if ((string) ($data['id'] ?? '') !== $businessId) {
            throw new WhatsAppOnboardingException('business_mismatch', 'The selected Meta business portfolio could not be verified.');
        }

        return $businessId;
    }

    private function resolveAndValidatePhoneNumber(
        string $wabaId,
        ?string $suppliedPhoneNumberId,
        string $accessToken,
        string $mode
    ): array {
        $numbers = $this->fetchPhoneNumbers($wabaId, $accessToken);
        $suppliedPhoneNumberId = trim((string) $suppliedPhoneNumberId);

        if ($suppliedPhoneNumberId !== '') {
            $phone = collect($numbers)->first(fn ($number) => (string) ($number['id'] ?? '') === $suppliedPhoneNumberId);
        } elseif (count($numbers) === 1) {
            $phone = $numbers[0];
        } else {
            $phone = null;
        }

        if (! is_array($phone) || blank($phone['id'] ?? null)) {
            throw new WhatsAppOnboardingException('phone_not_in_waba', 'Meta did not return one verified phone number for the selected WhatsApp account.');
        }

        $verifiedPhone = $this->fetchPhoneNumber((string) $phone['id'], $accessToken);
        if ((string) ($verifiedPhone['id'] ?? '') !== (string) $phone['id']) {
            throw new WhatsAppOnboardingException('phone_validation_mismatch', 'The selected WhatsApp phone number could not be verified.');
        }

        $isOnBusinessApp = $this->asBoolean($verifiedPhone['is_on_biz_app'] ?? $phone['is_on_biz_app'] ?? null);

        if ($mode === self::MODE_BUSINESS_APP && $isOnBusinessApp !== true) {
            throw new WhatsAppOnboardingException(
                'business_app_not_confirmed',
                'Meta did not confirm that this number remains on the WhatsApp Business app. Nothing was connected.'
            );
        }

        if ($mode === self::MODE_CLOUD_API && $isOnBusinessApp === true) {
            throw new WhatsAppOnboardingException(
                'business_app_number_in_cloud_flow',
                'This number is still on the WhatsApp Business app. Use the existing-number onboarding choice instead.'
            );
        }

        return array_merge($phone, $verifiedPhone);
    }

    private function assertTenantAssetIsAvailable(Company $company, string $phoneNumberId): void
    {
        $claimed = Company::query()
            ->where('meta_phone_number_id', $phoneNumberId)
            ->where('id', '!=', $company->id)
            ->exists();

        if ($claimed) {
            throw new WhatsAppOnboardingException('phone_already_claimed', 'This WhatsApp number is already connected to another SayaraForce company.');
        }
    }

    private function markSessionFailed(int $sessionId, WhatsAppOnboardingException $exception): void
    {
        $session = DB::table('whatsapp_connect_sessions')->where('id', $sessionId)->first();
        $payload = is_array($decoded = json_decode((string) ($session->payload ?? ''), true))
            ? $decoded
            : [];
        $payload['failure'] = array_filter([
            'reason' => $exception->reason,
            'provider' => $exception->safeContext ?: null,
        ], fn ($value) => $value !== null);

        DB::table('whatsapp_connect_sessions')->where('id', $sessionId)->update([
            'status' => 'failed',
            'error_code' => $exception->reason,
            'error_message' => $exception->getMessage(),
            'payload' => json_encode($payload),
            'updated_at' => now(),
        ]);
    }

    private function audit(
        int $companyId,
        ?int $userId,
        string $event,
        string $status,
        ?string $mode = null,
        ?string $wabaId = null,
        ?string $phoneNumberId = null,
        array $context = []
    ): void {
        if (! Schema::hasTable('whatsapp_connection_audits')) {
            return;
        }

        WhatsAppConnectionAudit::query()->create([
            'company_id' => $companyId,
            'user_id' => $userId,
            'event' => $event,
            'status' => $status,
            'connection_mode' => $mode,
            'waba_id' => $wabaId,
            'phone_number_id' => $phoneNumberId,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }

    private function decryptStoredAccessToken(Company $company): string
    {
        if (blank($company->meta_access_token)) {
            throw new WhatsAppOnboardingException('missing_stored_token', 'The WhatsApp connection token is unavailable. Reconnect WhatsApp.');
        }

        try {
            return Crypt::decryptString((string) $company->meta_access_token);
        } catch (\Throwable $exception) {
            throw new WhatsAppOnboardingException(
                'stored_token_not_encrypted',
                'The stored WhatsApp connection is not using the secure credential format. Reconnect WhatsApp.',
                previous: $exception
            );
        }
    }

    private function resolveTokenExpiry(array $payload): ?Carbon
    {
        $expiresIn = $payload['expires_in'] ?? null;

        return is_numeric($expiresIn) && (int) $expiresIn > 0
            ? now()->addSeconds((int) $expiresIn)
            : null;
    }

    private function successfulJson(Response $response, string $reason, string $safeMessage): array
    {
        if (! $response->successful()) {
            throw new WhatsAppOnboardingException(
                $reason,
                $safeMessage,
                safeContext: $this->metaResponseContext($response, $safeMessage)
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new WhatsAppOnboardingException(
                $reason,
                $safeMessage,
                safeContext: $this->metaResponseContext($response, 'Meta returned an unreadable response.')
            );
        }

        return $data;
    }

    private function metaResponseContext(Response $response, string $fallbackMessage): array
    {
        $message = data_get($response->json(), 'error.message');

        return array_filter([
            'http_status' => $response->status(),
            'meta_error_code' => data_get($response->json(), 'error.code'),
            'meta_error_type' => $this->sanitizeMetaValue(data_get($response->json(), 'error.type')),
            'message' => $this->sanitizeMetaValue(is_string($message) ? $message : $fallbackMessage),
        ], fn ($value) => $value !== null && $value !== '');
    }

    private function sanitizeMetaValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $sanitized = strip_tags((string) $value);
        $sanitized = preg_replace('/(?:EA[A-Za-z0-9_-]{20,}|[A-Za-z0-9_-]{40,})/', '[redacted credential]', $sanitized);
        $sanitized = preg_replace('/\+?\d[\d\s().-]{6,}\d/', '[redacted identifier]', (string) $sanitized);

        return Str::limit(trim((string) $sanitized), 240, '…');
    }

    private function graphUrl(string $path): string
    {
        return $this->graphBase.'/'.$this->graphVersion.'/'.ltrim($path, '/');
    }

    private function assertAppCredentials(): void
    {
        if (blank($this->appId) || blank($this->appSecret)) {
            throw new WhatsAppOnboardingException('app_credentials_missing', 'Meta app configuration is incomplete.');
        }
    }

    private function asBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
    }

    private function maskPhone(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits === '' ? null : str_repeat('*', max(strlen($digits) - 4, 0)).substr($digits, -4);
    }

    private function maskIdentifier(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : str_repeat('*', max(strlen($value) - 6, 0)).substr($value, -6);
    }
}
