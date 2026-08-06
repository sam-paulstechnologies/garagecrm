<?php

namespace App\Support\Staging;

use RuntimeException;

class StagingSafety
{
    public function assertRuntimeIsolated(bool $destructive = false): void
    {
        if (! app()->environment('staging')) {
            throw new RuntimeException('Staging operation refused: APP_ENV must be staging.');
        }

        $expectedHost = strtolower(trim((string) config('staging.expected_host')));
        $actualHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        if ($expectedHost === '' || ! hash_equals($expectedHost, $actualHost)) {
            throw new RuntimeException('Staging operation refused: APP_URL does not match the approved staging host.');
        }

        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");
        $host = strtolower(trim((string) config("database.connections.{$connection}.host")));
        $expectedDatabase = trim((string) config('staging.expected_database'));

        if ($connection !== 'mysql' || $expectedDatabase === '' || ! hash_equals($expectedDatabase, $database)) {
            throw new RuntimeException('Staging operation refused: the database identity is not the approved staging database.');
        }

        $localHost = in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
        $schemaValidationMode = (bool) config('staging.schema_validation_mode');
        $localSchemaValidation = $schemaValidationMode
            && app()->runningInConsole()
            && $localHost
            && str_contains(strtolower($database), 'staging_validation')
            && (bool) config('staging.schema_baseline_approved');

        if ($schemaValidationMode && ! $localSchemaValidation) {
            throw new RuntimeException('Staging schema validation refused: the local validation safety contract is incomplete.');
        }

        if (! $localSchemaValidation && ($host === '' || $localHost || ! str_contains($host, 'staging'))) {
            throw new RuntimeException('Staging operation refused: the database host is not staging-specific.');
        }

        if ($this->contains($this->csv(config('staging.production.app_urls')), (string) config('app.url'))) {
            throw new RuntimeException('Staging operation refused: APP_URL matches a production URL.');
        }

        $productionDatabaseHosts = $this->csv(config('staging.production.database_hosts'));
        if ($destructive && $productionDatabaseHosts === []) {
            throw new RuntimeException('Destructive staging operation refused: the production database-host denylist is empty.');
        }
        if ($this->contains($productionDatabaseHosts, $host)) {
            throw new RuntimeException('Staging operation refused: database host matches the production denylist.');
        }

        if ($destructive && ! config('staging.schema_baseline_approved')) {
            throw new RuntimeException('Destructive staging operation refused: the repository schema baseline has not been approved.');
        }
    }

    public function assertProviderAssetsAllowed(?string $wabaId, ?string $phoneNumberId): void
    {
        if (! app()->environment('staging')) {
            return;
        }

        $this->assertAssetAllowed(
            $wabaId,
            $this->csv(config('staging.production.waba_ids')),
            $this->csv(config('staging.meta.allowed_waba_ids')),
            'WABA'
        );
        $this->assertAssetAllowed(
            $phoneNumberId,
            $this->csv(config('staging.production.phone_number_ids')),
            $this->csv(config('staging.meta.allowed_phone_number_ids')),
            'phone number'
        );
    }

    public function assertWhatsAppOutboundAllowed(string $recipient, ?string $wabaId = null, ?string $phoneNumberId = null): void
    {
        if (! app()->environment('staging')) {
            return;
        }

        if (! config('staging.communications.whatsapp_outbound_enabled')) {
            throw new RuntimeException('Staging WhatsApp outbound messaging is disabled.');
        }

        $this->assertProviderAssetsAllowed($wabaId, $phoneNumberId);
        $this->assertPhoneRecipientAllowed($recipient);
    }

    public function assertSmsOutboundAllowed(string $recipient): void
    {
        if (! app()->environment('staging')) {
            return;
        }

        if (! config('staging.communications.sms_outbound_enabled')) {
            throw new RuntimeException('Staging SMS outbound messaging is disabled.');
        }

        $this->assertPhoneRecipientAllowed($recipient);
    }

    public function emailRecipientsAreAllowed(array $addresses): bool
    {
        if (! app()->environment('staging')) {
            return true;
        }

        $allowedAddresses = array_map('strtolower', $this->csv(config('staging.communications.allowed_email_recipients')));
        $allowedDomains = array_map(fn (string $value): string => ltrim(strtolower($value), '@'), $this->csv(config('staging.communications.allowed_email_domains')));
        if ($addresses === [] || ($allowedAddresses === [] && $allowedDomains === [])) {
            return false;
        }

        foreach ($addresses as $address) {
            $address = strtolower(trim((string) $address));
            $domain = str_contains($address, '@') ? substr(strrchr($address, '@'), 1) : '';
            if (! in_array($address, $allowedAddresses, true) && ! in_array($domain, $allowedDomains, true)) {
                return false;
            }
        }

        return true;
    }

    public function legacyCompanyResolutionAllowed(): bool
    {
        return ! app()->environment('staging') || (bool) config('staging.meta.allow_legacy_company_resolution');
    }

    private function assertAssetAllowed(?string $assetId, array $denylist, array $allowlist, string $label): void
    {
        $assetId = trim((string) $assetId);
        if ($assetId === '') {
            return;
        }
        if ($denylist === [] || $allowlist === []) {
            throw new RuntimeException("Staging {$label} validation is not configured; operation refused.");
        }
        if ($this->contains($denylist, $assetId) || ! $this->contains($allowlist, $assetId)) {
            throw new RuntimeException("Staging {$label} is not an approved test asset.");
        }
    }

    private function assertPhoneRecipientAllowed(string $recipient): void
    {
        $recipient = preg_replace('/\D+/', '', $recipient) ?: '';
        $allowed = array_map(fn (string $value): string => preg_replace('/\D+/', '', $value) ?: '', $this->csv(config('staging.communications.allowed_phone_recipients')));
        if ($recipient === '' || $allowed === [] || ! in_array($recipient, $allowed, true)) {
            throw new RuntimeException('Staging recipient is not on the approved test allowlist.');
        }
    }

    private function csv(mixed $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value)), fn (string $item): bool => $item !== ''));
    }

    private function contains(array $values, string $candidate): bool
    {
        foreach ($values as $value) {
            if (hash_equals(strtolower(trim((string) $value)), strtolower(trim($candidate)))) {
                return true;
            }
        }

        return false;
    }
}
