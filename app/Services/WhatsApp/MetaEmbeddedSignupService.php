<?php

namespace App\Services\WhatsApp;

use App\Models\System\Company;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class MetaEmbeddedSignupService
{
    protected string $graphBase;
    protected string $graphVersion;
    protected ?string $appId;
    protected ?string $appSecret;

    public function __construct()
    {
        $this->graphBase = rtrim(
            (string) config('services.meta.graph_base', config('services.whatsapp.meta.graph_base', 'https://graph.facebook.com')),
            '/'
        );

        $this->graphVersion = trim(
            (string) config('services.meta.api_version', config('services.whatsapp.meta.api_version', 'v21.0')),
            '/'
        );

        $this->appId = config('services.meta.app_id')
            ?: config('services.meta_leads.app_id')
            ?: config('services.facebook.client_id')
            ?: env('META_APP_ID');

        $this->appSecret = config('services.meta.app_secret')
            ?: config('services.meta_leads.app_secret')
            ?: config('services.facebook.client_secret')
            ?: env('META_APP_SECRET');
    }

    public function createState(int $companyId, ?int $userId = null, string $connectionMode = 'coexistence'): string
    {
        $state = Str::random(48);
        $connectionMode = $this->normalizeConnectionMode($connectionMode);

        if (Schema::hasTable('whatsapp_connect_sessions')) {
            $now = now();

            try {
                DB::table('whatsapp_connect_sessions')->insert([
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'state' => $state,
                    'status' => 'started',
                    'payload' => json_encode([
                        'connection_mode' => $connectionMode,
                        'started_from' => 'embedded_signup',
                        'started_at' => $now->toIso8601String(),
                    ]),
                    'started_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } catch (\Throwable $e) {
                logger()->warning('[SF-WA Connect] Failed to store connect session', [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'connection_mode' => $connectionMode,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $state;
    }

    public function getSessionConnectionMode(?string $state): ?string
    {
        if (blank($state) || ! Schema::hasTable('whatsapp_connect_sessions')) {
            return null;
        }

        try {
            $row = DB::table('whatsapp_connect_sessions')
                ->where('state', $state)
                ->first();

            if (! $row || blank($row->payload ?? null)) {
                return null;
            }

            $payload = json_decode((string) $row->payload, true);

            if (! is_array($payload)) {
                return null;
            }

            return $this->normalizeConnectionMode($payload['connection_mode'] ?? null);
        } catch (\Throwable $e) {
            logger()->warning('[SF-WA Connect] Failed to read session connection mode', [
                'state' => $state,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function markSessionFailed(string $state, string $message, array $payload = []): void
    {
        if (! Schema::hasTable('whatsapp_connect_sessions')) {
            return;
        }

        try {
            DB::table('whatsapp_connect_sessions')
                ->where('state', $state)
                ->update([
                    'status' => 'failed',
                    'error_message' => $message,
                    'payload' => json_encode($payload),
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            logger()->warning('[SF-WA Connect] Failed to mark session failed', [
                'state' => $state,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function markSessionCompleted(string $state, Company $company, array $payload = []): void
    {
        if (! Schema::hasTable('whatsapp_connect_sessions')) {
            return;
        }

        try {
            DB::table('whatsapp_connect_sessions')
                ->where('state', $state)
                ->update([
                    'status' => 'completed',
                    'meta_business_id' => $payload['business_id'] ?? null,
                    'waba_id' => $company->meta_waba_id ?? null,
                    'phone_number_id' => $company->meta_phone_number_id ?? null,
                    'display_phone_number' => $payload['display_phone_number'] ?? null,
                    'payload' => json_encode($payload),
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);
        } catch (\Throwable $e) {
            logger()->warning('[SF-WA Connect] Failed to mark session completed', [
                'state' => $state,
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function exchangeCodeForAccessToken(string $code): array
    {
        $this->assertAppCredentials();

        $response = Http::timeout(30)
            ->acceptJson()
            ->get($this->graphUrl('oauth/access_token'), [
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'code' => $code,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Meta token exchange failed: '.$response->body());
        }

        $data = $response->json();

        if (blank($data['access_token'] ?? null)) {
            throw new RuntimeException('Meta token exchange did not return access_token.');
        }

        return $data;
    }

    public function fetchPhoneNumbers(string $wabaId, string $accessToken): array
    {
        $response = Http::timeout(30)
            ->withToken($accessToken)
            ->acceptJson()
            ->get($this->graphUrl($wabaId.'/phone_numbers'), [
                'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status,status',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to fetch WhatsApp phone numbers: '.$response->body());
        }

        return $response->json('data') ?? [];
    }

    public function fetchPhoneNumber(string $phoneNumberId, string $accessToken): array
    {
        $response = Http::timeout(30)
            ->withToken($accessToken)
            ->acceptJson()
            ->get($this->graphUrl($phoneNumberId), [
                'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status,status',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to fetch WhatsApp phone number: '.$response->body());
        }

        return $response->json() ?? [];
    }

    public function saveConnectionToCompany(
        Company $company,
        string $accessToken,
        ?string $wabaId,
        ?string $phoneNumberId,
        ?string $businessId = null,
        ?string $displayPhoneNumber = null,
        array $metaPayload = [],
        string $connectionMode = 'coexistence'
    ): Company {
        if (blank($phoneNumberId)) {
            throw new RuntimeException('phone_number_id is required to connect WhatsApp.');
        }

        $connectionMode = $this->normalizeConnectionMode($connectionMode);

        $company->meta_phone_number_id = $phoneNumberId;
        $company->meta_access_token = $this->encryptToken($accessToken);
        $company->meta_waba_id = $wabaId;
        $company->is_whatsapp_active = true;

        if (Schema::hasColumn('companies', 'meta_token_expires_at')) {
            $company->meta_token_expires_at = $this->resolveTokenExpiry($metaPayload);
        }

        if (Schema::hasColumn('companies', 'meta_verify_token') && blank($company->meta_verify_token)) {
            $company->meta_verify_token = 'sfwa_'.$company->id.'_'.Str::random(32);
        }

        if (Schema::hasColumn('companies', 'meta_business_id')) {
            $company->meta_business_id = $businessId;
        }

        if (Schema::hasColumn('companies', 'meta_display_phone_number')) {
            $company->meta_display_phone_number = $displayPhoneNumber;
        }

        if (Schema::hasColumn('companies', 'whatsapp_connection_mode')) {
            $company->whatsapp_connection_mode = $connectionMode;
        }

        if (Schema::hasColumn('companies', 'whatsapp_coexistence_enabled')) {
            $company->whatsapp_coexistence_enabled = $connectionMode === 'coexistence';
        }

        if (Schema::hasColumn('companies', 'whatsapp_coexistence_status')) {
            $company->whatsapp_coexistence_status = $connectionMode === 'coexistence'
                ? 'connected'
                : null;
        }

        if (Schema::hasColumn('companies', 'whatsapp_onboarding_source')) {
            $company->whatsapp_onboarding_source = $connectionMode === 'coexistence'
                ? 'embedded_signup_coexistence'
                : 'embedded_signup_cloud_api';
        }

        if (Schema::hasColumn('companies', 'whatsapp_connected_at')) {
            $company->whatsapp_connected_at = now();
        }

        $company->save();

        logger()->info('[SF-WA Connect] WhatsApp connected for company', [
            'company_id' => $company->id,
            'waba_id' => $wabaId,
            'phone_number_id' => $phoneNumberId,
            'display_phone_number' => $displayPhoneNumber,
            'connection_mode' => $connectionMode,
            'coexistence_enabled' => $connectionMode === 'coexistence',
        ]);

        return $company->fresh();
    }

    public function disconnectCompany(Company $company): Company
    {
        $company->is_whatsapp_active = false;

        if (Schema::hasColumn('companies', 'meta_access_token')) {
            $company->meta_access_token = null;
        }

        if (Schema::hasColumn('companies', 'meta_token_expires_at')) {
            $company->meta_token_expires_at = null;
        }

        if (Schema::hasColumn('companies', 'whatsapp_connection_mode')) {
            $company->whatsapp_connection_mode = 'manual';
        }

        if (Schema::hasColumn('companies', 'whatsapp_coexistence_enabled')) {
            $company->whatsapp_coexistence_enabled = false;
        }

        if (Schema::hasColumn('companies', 'whatsapp_coexistence_status')) {
            $company->whatsapp_coexistence_status = 'disconnected';
        }

        $company->save();

        logger()->info('[SF-WA Connect] WhatsApp disconnected for company', [
            'company_id' => $company->id,
        ]);

        return $company->fresh();
    }

    public function connectionStatus(Company $company): array
    {
        $connectionMode = $company->whatsapp_connection_mode ?? null;

        if (blank($connectionMode)) {
            $connectionMode = ((bool) ($company->is_whatsapp_active ?? false) && filled($company->meta_phone_number_id ?? null))
                ? 'cloud_api'
                : 'manual';
        }

        return [
            'is_connected' => filled($company->meta_phone_number_id ?? null)
                && filled($company->meta_access_token ?? null)
                && (bool) ($company->is_whatsapp_active ?? false),

            'is_active' => (bool) ($company->is_whatsapp_active ?? false),
            'waba_id' => $company->meta_waba_id ?? null,
            'phone_number_id' => $company->meta_phone_number_id ?? null,
            'business_id' => $company->meta_business_id ?? null,
            'display_phone_number' => $company->meta_display_phone_number ?? null,
            'token_expires_at' => $company->meta_token_expires_at ?? null,
            'verify_token' => $company->meta_verify_token ?? null,

            'connection_mode' => $connectionMode,
            'coexistence_enabled' => (bool) ($company->whatsapp_coexistence_enabled ?? false),
            'coexistence_status' => $company->whatsapp_coexistence_status ?? null,
            'onboarding_source' => $company->whatsapp_onboarding_source ?? null,
            'last_echo_at' => $company->whatsapp_last_echo_at ?? null,
            'connected_at' => $company->whatsapp_connected_at ?? null,
        ];
    }

    protected function resolveTokenExpiry(array $metaPayload): ?Carbon
    {
        $expiresIn = $metaPayload['expires_in'] ?? null;

        if (is_numeric($expiresIn) && (int) $expiresIn > 0) {
            return now()->addSeconds((int) $expiresIn);
        }

        return null;
    }

    protected function encryptToken(string $token): string
    {
        try {
            return Crypt::encryptString($token);
        } catch (\Throwable $e) {
            logger()->warning('[SF-WA Connect] Token encryption failed, storing raw token', [
                'error' => $e->getMessage(),
            ]);

            return $token;
        }
    }

    protected function normalizeConnectionMode(?string $mode): string
    {
        $mode = strtolower(trim((string) $mode));

        return in_array($mode, ['manual', 'cloud_api', 'coexistence'], true)
            ? $mode
            : 'coexistence';
    }

    protected function graphUrl(string $path): string
    {
        return $this->graphBase.'/'.$this->graphVersion.'/'.ltrim($path, '/');
    }

    protected function assertAppCredentials(): void
    {
        if (blank($this->appId) || blank($this->appSecret)) {
            throw new RuntimeException('Meta app credentials are missing. Please set META_APP_ID and META_APP_SECRET.');
        }
    }
}