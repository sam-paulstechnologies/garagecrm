<?php

namespace App\Messaging;

class MetaUatReadiness
{
    /**
     * Return presence and safety signals only. Configuration values are never
     * included because this result is safe to expose in restricted diagnostics.
     *
     * @return array<string, mixed>
     */
    public function report(): array
    {
        $checks = [
            'staging_environment' => app()->environment('staging'),
            'staging_host' => $this->isExpectedHost(),
            'meta_app_id_configured' => $this->configured(config('services.meta.app_id')),
            'meta_app_secret_configured' => $this->configured(config('services.meta.app_secret')),
            'coexistence_config_id_configured' => $this->configured(
                config('services.meta.whatsapp_embedded_signup.business_app_config_id')
            ),
            'cloud_api_config_id_configured' => $this->configured(
                config('services.meta.whatsapp_embedded_signup.cloud_api_config_id')
            ),
            'webhook_verify_token_configured' => $this->configured(config('services.meta.whatsapp_verify_token')),
            'staging_waba_allowlist_configured' => $this->csvConfigured(config('staging.meta.allowed_waba_ids')),
            'staging_phone_allowlist_configured' => $this->csvConfigured(config('staging.meta.allowed_phone_number_ids')),
            'production_waba_denylist_configured' => $this->csvConfigured(config('staging.production.waba_ids')),
            'production_phone_denylist_configured' => $this->csvConfigured(config('staging.production.phone_number_ids')),
            'test_recipient_allowlist_configured' => $this->csvConfigured(
                config('staging.communications.allowed_phone_recipients')
            ),
            'legacy_company_resolution_disabled' => ! (bool) config('staging.meta.allow_legacy_company_resolution'),
            'whatsapp_outbound_disabled' => ! (bool) config('staging.communications.whatsapp_outbound_enabled'),
            'sms_outbound_disabled' => ! (bool) config('staging.communications.sms_outbound_enabled'),
        ];

        $liveConfigurationChecks = array_diff_key($checks, array_flip([
            'whatsapp_outbound_disabled',
            'sms_outbound_disabled',
        ]));

        return [
            'status' => 'ok',
            'engineering_ready' => $checks['staging_environment']
                && $checks['staging_host']
                && $checks['legacy_company_resolution_disabled']
                && $checks['whatsapp_outbound_disabled']
                && $checks['sms_outbound_disabled'],
            'live_uat_configuration_ready' => ! in_array(false, $liveConfigurationChecks, true),
            'outbound_test_authorized' => false,
            'checks' => $checks,
            'endpoints' => [
                'webhook_callback' => rtrim((string) config('app.url'), '/').'/api/v1/webhooks/meta/whatsapp',
                'embedded_signup' => rtrim((string) config('app.url'), '/').'/admin/whatsapp/connect',
            ],
        ];
    }

    private function isExpectedHost(): bool
    {
        $expected = strtolower(trim((string) config('staging.expected_host')));
        $actual = strtolower(trim((string) parse_url((string) config('app.url'), PHP_URL_HOST)));

        return $expected !== '' && $actual !== '' && hash_equals($expected, $actual);
    }

    private function configured(mixed $value): bool
    {
        return trim((string) $value) !== '';
    }

    private function csvConfigured(mixed $value): bool
    {
        return collect(explode(',', (string) $value))
            ->map(fn (string $item): string => trim($item))
            ->contains(fn (string $item): bool => $item !== '');
    }
}
