<?php

namespace App\Messaging\WhatsApp;

use App\Messaging\Exceptions\MessagingProvisioningException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MetaApiClient
{
    public function signupConfiguration(string $mode): array
    {
        $settings = (array) config('messaging.providers.meta_whatsapp', []);
        $isBusinessApp = $mode === 'business_app_onboarding';
        $configId = $isBusinessApp
            ? ($settings['business_app_config_id'] ?? null)
            : ($settings['cloud_api_config_id'] ?? null);

        $extras = [
            'setup' => (object) [],
            'version' => (string) ($settings['embedded_signup_version'] ?? 'v4'),
            'sessionInfoVersion' => (string) ($settings['session_info_version'] ?? '3'),
        ];

        if ($isBusinessApp) {
            $extras['featureType'] = 'whatsapp_business_app_onboarding';
        }

        return [
            'app_id' => $settings['app_id'] ?? null,
            'config_id' => $configId,
            'graph_version' => $settings['api_version'] ?? 'v25.0',
            'connection_mode' => $mode,
            'extras' => $extras,
            'is_configured' => filled($settings['app_id'] ?? null)
                && filled($settings['app_secret'] ?? null)
                && filled($configId),
        ];
    }

    public function exchangeCode(string $code): array
    {
        $this->assertAppConfigured();

        return $this->json(
            Http::timeout(30)->acceptJson()->get($this->url('oauth/access_token'), [
                'client_id' => $this->appId(),
                'client_secret' => $this->appSecret(),
                'code' => $code,
            ]),
            'code_exchange_failed',
            'Meta rejected or expired the authorization. Restart the connection.',
        );
    }

    public function inspectToken(string $token): array
    {
        $this->assertAppConfigured();
        $payload = $this->json(
            Http::timeout(30)->acceptJson()->get($this->url('debug_token'), [
                'input_token' => $token,
                'access_token' => $this->appId().'|'.$this->appSecret(),
            ]),
            'token_inspection_failed',
            'Meta could not validate the granted access.',
        );
        $data = (array) ($payload['data'] ?? []);

        if (! ($data['is_valid'] ?? false) || (string) ($data['app_id'] ?? '') !== $this->appId()) {
            throw new MessagingProvisioningException('invalid_token', 'The Meta authorization is not valid for SayaraForce.');
        }

        $scopes = array_map('strval', (array) ($data['scopes'] ?? []));
        foreach (['whatsapp_business_management', 'whatsapp_business_messaging'] as $scope) {
            if (! in_array($scope, $scopes, true)) {
                throw new MessagingProvisioningException('missing_permission', 'Meta did not grant all required WhatsApp permissions.');
            }
        }

        return $data;
    }

    public function getWaba(string $wabaId, string $token): array
    {
        return $this->authorizedGet($wabaId, $token, [
            'fields' => 'id,name,owner_business_info',
        ], 'waba_inaccessible', 'Meta could not access the selected WhatsApp account.');
    }

    public function getBusiness(string $businessId, string $token): array
    {
        return $this->authorizedGet($businessId, $token, ['fields' => 'id,name'],
            'business_inaccessible', 'Meta could not validate the selected business.');
    }

    public function getPhoneNumbers(string $wabaId, string $token): array
    {
        $payload = $this->authorizedGet($wabaId.'/phone_numbers', $token, [
            'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status,status,platform_type,is_on_biz_app,name_status',
            'limit' => 100,
        ], 'phone_discovery_failed', 'Meta could not find an eligible WhatsApp number.');

        return array_values(array_filter((array) ($payload['data'] ?? []), 'is_array'));
    }

    public function getPhone(string $phoneNumberId, string $token): array
    {
        return $this->authorizedGet($phoneNumberId, $token, [
            'fields' => 'id,display_phone_number,verified_name,quality_rating,code_verification_status,status,platform_type,is_on_biz_app,name_status',
        ], 'phone_inaccessible', 'Meta could not verify the connected WhatsApp number.');
    }

    public function subscribeWaba(string $wabaId, string $token): void
    {
        $callbackOverride = $this->callbackOverride();
        $payload = $this->json(
            Http::timeout(30)->withToken($token)->acceptJson()->post(
                $this->url($wabaId.'/subscribed_apps'),
                $callbackOverride ?? [],
            ),
            'subscription_failed',
            'Meta did not accept the webhook subscription.',
        );

        $confirmed = filter_var($payload['success'] ?? false, FILTER_VALIDATE_BOOL)
            || ($callbackOverride !== null && $this->appIsSubscribed($payload));

        if (! $confirmed) {
            throw new MessagingProvisioningException('subscription_unconfirmed', 'Meta did not confirm the webhook subscription.');
        }
    }

    public function assignConfiguredSystemUser(string $wabaId): bool
    {
        $userId = trim((string) config('messaging.providers.meta_whatsapp.system_user_id'));
        $token = trim((string) config('messaging.providers.meta_whatsapp.system_user_access_token'));
        if ($userId === '' || $token === '') {
            return false;
        }

        $payload = $this->json(
            Http::timeout(30)->withToken($token)->acceptJson()->post($this->url($wabaId.'/assigned_users'), [
                'user' => $userId,
                'tasks' => ['MANAGE', 'DEVELOP'],
            ]),
            'system_user_assignment_failed',
            'Meta could not assign the configured platform access to this WhatsApp account.',
        );

        if (! filter_var($payload['success'] ?? false, FILTER_VALIDATE_BOOL)) {
            throw new MessagingProvisioningException('system_user_assignment_unconfirmed', 'Meta did not confirm platform access for this WhatsApp account.');
        }

        return true;
    }

    public function getWabaSubscriptions(string $wabaId, string $token): array
    {
        return $this->authorizedGet($wabaId.'/subscribed_apps', $token, [],
            'subscription_read_failed', 'Meta could not verify the webhook subscription.');
    }

    public function appIsSubscribed(array $payload): bool
    {
        $expected = $this->appId();
        $expectedCallback = $this->callbackOverride()['override_callback_uri'] ?? null;

        foreach ((array) ($payload['data'] ?? []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $id = data_get($row, 'whatsapp_business_api_data.id') ?? ($row['id'] ?? null);
            if ((string) $id === $expected) {
                if ($expectedCallback === null) {
                    return true;
                }

                $actualCallback = trim((string) ($row['override_callback_uri'] ?? ''));
                if ($actualCallback !== '' && hash_equals($expectedCallback, $actualCallback)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Return the per-WABA callback override payload, or null when the feature is
     * deliberately disabled. Values are never included in exception context.
     */
    private function callbackOverride(): ?array
    {
        if (! (bool) config('messaging.providers.meta_whatsapp.waba_callback_override_enabled', false)) {
            return null;
        }

        $callback = trim((string) config('messaging.providers.meta_whatsapp.webhook_callback_url'));
        $verifyToken = trim((string) config('messaging.providers.meta_whatsapp.webhook_verify_token'));
        $canonical = rtrim((string) config('app.url'), '/').'/api/v1/webhooks/meta/whatsapp';
        $parts = parse_url($callback);

        $validCallback = is_array($parts)
            && strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && filled($parts['host'] ?? null)
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && ! isset($parts['query'])
            && ! isset($parts['fragment'])
            && $callback === $canonical;

        if (! $validCallback || $verifyToken === '') {
            throw new MessagingProvisioningException(
                'unsafe_webhook_callback_override',
                'The staging-specific Meta webhook callback is not configured safely.',
            );
        }

        return [
            'override_callback_uri' => $callback,
            'verify_token' => $verifyToken,
        ];
    }

    public function getAppWebhookFields(): array
    {
        $this->assertAppConfigured();
        $payload = $this->json(
            Http::timeout(30)->acceptJson()->get($this->url($this->appId().'/subscriptions'), [
                'access_token' => $this->appId().'|'.$this->appSecret(),
            ]),
            'webhook_fields_unavailable',
            'Meta could not verify the application webhook fields.',
        );

        $fields = [];
        foreach ((array) ($payload['data'] ?? []) as $subscription) {
            if (($subscription['object'] ?? null) !== 'whatsapp_business_account') {
                continue;
            }
            foreach ((array) ($subscription['fields'] ?? []) as $field) {
                $fields[] = is_array($field) ? ($field['name'] ?? null) : $field;
            }
        }

        return array_values(array_unique(array_filter(array_map('strval', $fields))));
    }

    public function appId(): string
    {
        return trim((string) config('messaging.providers.meta_whatsapp.app_id'));
    }

    private function appSecret(): string
    {
        return trim((string) config('messaging.providers.meta_whatsapp.app_secret'));
    }

    private function authorizedGet(string $path, string $token, array $query, string $reason, string $message): array
    {
        return $this->json(
            Http::timeout(30)->withToken($token)->acceptJson()->get($this->url($path), $query),
            $reason,
            $message,
        );
    }

    private function json(Response $response, string $reason, string $message): array
    {
        $payload = $response->json();
        if ($response->successful() && is_array($payload)) {
            return $payload;
        }

        throw new MessagingProvisioningException($reason, $message, array_filter([
            'http_status' => $response->status(),
            'provider_error_code' => data_get($payload, 'error.code'),
            'provider_error_subcode' => data_get($payload, 'error.error_subcode'),
            'provider_error_type' => data_get($payload, 'error.type'),
        ], fn ($value): bool => $value !== null && $value !== ''));
    }

    private function url(string $path): string
    {
        $base = rtrim((string) config('messaging.providers.meta_whatsapp.graph_base'), '/');
        $version = trim((string) config('messaging.providers.meta_whatsapp.api_version', 'v25.0'), '/');

        return $base.'/'.$version.'/'.ltrim($path, '/');
    }

    private function assertAppConfigured(): void
    {
        if ($this->appId() === '' || $this->appSecret() === '') {
            throw new MessagingProvisioningException('meta_not_configured', 'Meta onboarding is not configured yet.');
        }
    }
}
