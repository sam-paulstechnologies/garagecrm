<?php

namespace App\Support\Staging;

use RuntimeException;

class StagingSafety
{
    /**
     * Environments we can positively recognise as *not* a staging box and safe
     * to leave unguarded. Anything outside this set (a blank/typo'd APP_ENV, a
     * mislabeled 'sandbox'/'uat'/'prod' box, etc.) is treated as ambiguous and
     * fails closed for outbound + provider-asset assertions. See
     * {@see self::outboundGuardActive()}.
     */
    private const RECOGNISED_UNGUARDED_ENVIRONMENTS = ['production', 'local', 'testing'];

    /**
     * Required production denylists. Emptiness is a configuration failure while
     * the guard is active. The operator MUST populate these with the REAL
     * production identifiers via the following env keys:
     *   - production_database_hosts   => STAGING_PRODUCTION_DB_HOST_DENYLIST
     *   - production_waba_ids         => STAGING_META_PRODUCTION_WABA_ID_DENYLIST
     *   - production_phone_number_ids => STAGING_META_PRODUCTION_PHONE_NUMBER_ID_DENYLIST
     */
    private const REQUIRED_DENYLISTS = [
        'production_database_hosts' => 'staging.production.database_hosts',
        'production_waba_ids' => 'staging.production.waba_ids',
        'production_phone_number_ids' => 'staging.production.phone_number_ids',
    ];

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
        if (! $this->outboundGuardActive()) {
            return;
        }

        // Defence in depth (M8): when the guard is active, the production
        // provider denylists MUST be populated. An empty required denylist is a
        // configuration failure, not an implicit "allow", even when the asset id
        // is absent — otherwise the assertion would silently complete.
        $this->assertRequiredDenylistsReady(['production_waba_ids', 'production_phone_number_ids']);

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
        if (! $this->outboundGuardActive()) {
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
        if (! $this->outboundGuardActive()) {
            return;
        }

        if (! config('staging.communications.sms_outbound_enabled')) {
            throw new RuntimeException('Staging SMS outbound messaging is disabled.');
        }

        $this->assertPhoneRecipientAllowed($recipient);
    }

    public function emailRecipientsAreAllowed(array $addresses): bool
    {
        if (! $this->outboundGuardActive()) {
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
        return ! $this->outboundGuardActive() || (bool) config('staging.meta.allow_legacy_company_resolution');
    }

    /**
     * Report whether every required production denylist is populated.
     *
     * Emptiness is made VISIBLE here so readiness/connection controls can fail
     * closed instead of silently completing (M8). No production values are ever
     * invented; the operator must supply them via the env keys documented on
     * {@see self::REQUIRED_DENYLISTS}.
     *
     * @return array{guard_active:bool,ready:bool,required:array<int,string>,empty:array<int,string>,env_keys:array<string,string>}
     */
    public function denylistReadiness(): array
    {
        $empty = [];
        foreach (self::REQUIRED_DENYLISTS as $name => $configKey) {
            if ($this->csv(config($configKey)) === []) {
                $empty[] = $name;
            }
        }

        return [
            'guard_active' => $this->outboundGuardActive(),
            'ready' => $empty === [],
            'required' => array_keys(self::REQUIRED_DENYLISTS),
            'empty' => $empty,
            'env_keys' => [
                'production_database_hosts' => 'STAGING_PRODUCTION_DB_HOST_DENYLIST',
                'production_waba_ids' => 'STAGING_META_PRODUCTION_WABA_ID_DENYLIST',
                'production_phone_number_ids' => 'STAGING_META_PRODUCTION_PHONE_NUMBER_ID_DENYLIST',
            ],
        ];
    }

    /**
     * The outbound / provider-asset safety guard.
     *
     * Safety does NOT rest on a single APP_ENV string (M31). The guard is ACTIVE
     * (staging restrictions apply / fail closed) whenever we cannot positively
     * prove the box is a recognised, safe, non-staging environment:
     *   - APP_ENV is 'staging'                                  -> active
     *   - an explicit safety flag is set (STAGING_SAFETY_ENFORCED) -> active
     *   - the expected staging host or database identity matches -> active
     *   - the environment is ambiguous/unknown (mislabeled box)  -> active (fail closed)
     *   - a recognised safe environment with no staging signals  -> inactive (allow)
     *
     * A mislabeled staging box (APP_ENV unset -> Laravel reports 'production',
     * or set to some other string) therefore still guards outbound as long as
     * either the explicit flag or the host/DB identity points at staging, and an
     * unrecognised environment name always fails closed.
     */
    public function outboundGuardActive(): bool
    {
        if (app()->environment('staging')) {
            return true;
        }

        if ($this->safetyModeEnforced()) {
            return true;
        }

        [$hostIndicatesStaging, $databaseIndicatesStaging] = $this->stagingIdentityIndicators();
        if ($hostIndicatesStaging || $databaseIndicatesStaging) {
            return true;
        }

        // Positively recognised, safe, non-staging environment: allow.
        if (app()->environment(self::RECOGNISED_UNGUARDED_ENVIRONMENTS)) {
            return false;
        }

        // Ambiguous / unknown environment: fail closed.
        return true;
    }

    private function safetyModeEnforced(): bool
    {
        return (bool) config('staging.safety_mode');
    }

    /**
     * @return array{0:bool,1:bool} [hostIndicatesStaging, databaseIndicatesStaging]
     */
    private function stagingIdentityIndicators(): array
    {
        $expectedHost = strtolower(trim((string) config('staging.expected_host')));
        $actualHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $hostIndicatesStaging = $expectedHost !== '' && $actualHost !== '' && hash_equals($expectedHost, $actualHost);

        $connection = (string) config('database.default');
        $database = trim((string) config("database.connections.{$connection}.database"));
        $expectedDatabase = trim((string) config('staging.expected_database'));
        $databaseIndicatesStaging = $expectedDatabase !== '' && $database !== '' && hash_equals($expectedDatabase, $database);

        return [$hostIndicatesStaging, $databaseIndicatesStaging];
    }

    /**
     * @param  array<int,string>  $names  keys of self::REQUIRED_DENYLISTS
     */
    private function assertRequiredDenylistsReady(array $names): void
    {
        foreach ($names as $name) {
            $configKey = self::REQUIRED_DENYLISTS[$name] ?? null;
            if ($configKey !== null && $this->csv(config($configKey)) === []) {
                throw new RuntimeException(
                    'Staging provider validation is not configured: a required production denylist is empty; operation refused.'
                );
            }
        }
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
