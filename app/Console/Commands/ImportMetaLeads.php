<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Models\Company;
use App\Models\Client\Lead;
use App\Services\Settings\SettingsStore;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Cache;

class ImportMetaLeads extends Command
{
    protected $signature = 'leads:import-meta
                            {--company= : Company ID (omit to run for ALL companies)}
                            {--limit=50 : Per-page fetch size}';

    protected $description = 'Import leads from Meta Lead Ads into GarageCRM';

    public function handle(): int
    {
        $limit     = max(1, (int) $this->option('limit'));
        $companyId = $this->option('company');

        // ── Single company (explicit) ──────────────────────────────────────────────
        if ($companyId) {
            $companyId = (int) $companyId;
            $this->info("Starting Meta lead import for company #{$companyId} (limit={$limit})");

            try {
                $count = $this->runForCompany($companyId, $limit);
                Cache::put("meta_import:company:{$companyId}:last_run_at", now()->toDateTimeString(), 86400);
                Cache::put("meta_import:company:{$companyId}:last_count", (int) $count, 86400);
                Cache::forget("meta_import:company:{$companyId}:last_error");

                $this->info("Meta import complete for company #{$companyId}. Imported {$count} lead(s).");
                return self::SUCCESS;
            } catch (\Throwable $e) {
                $msg = "Meta import failed for company #{$companyId}: {$e->getMessage()}";
                $this->error($msg);
                Cache::put("meta_import:company:{$companyId}:last_error", $msg, 86400);
                return self::FAILURE;
            }
        }

        // ── All companies (default) ───────────────────────────────────────────────
        $this->info('No company specified — importing for ALL companies…');

        $overall = 0;
        $hadError = false;

        $companies = Company::query()->select('id', 'name')->get();
        if ($companies->isEmpty()) {
            $this->warn('No companies found. Nothing to import.');
            return self::SUCCESS;
        }

        foreach ($companies as $company) {
            try {
                $this->line("→ Importing for company #{$company->id} ({$company->name}) …");
                $count = $this->runForCompany((int) $company->id, $limit);
                $overall += $count;

                Cache::put("meta_import:company:{$company->id}:last_run_at", now()->toDateTimeString(), 86400);
                Cache::put("meta_import:company:{$company->id}:last_count", (int) $count, 86400);
                Cache::forget("meta_import:company:{$company->id}:last_error");

                $this->line("✔ Company #{$company->id}: imported {$count} lead(s).");
            } catch (\Throwable $e) {
                $hadError = true;
                $msg = "Failed for company #{$company->id}: {$e->getMessage()}";
                $this->error($msg);
                \Log::error('[Meta Import] '.$msg, ['company_id' => $company->id, 'trace' => $e->getTraceAsString()]);
                Cache::put("meta_import:company:{$company->id}:last_error", $msg, 86400);
            }
        }

        $this->info("Meta import completed for all companies. Total imported: {$overall}.");

        return $hadError ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Run the import for a single company and return # of leads imported.
     */
    private function runForCompany(int $companyId, int $limit): int
    {
        $store   = new SettingsStore($companyId);
        $token   = $this->decryptIfNeeded($store->get('meta.access_token'));
        $apiVer  = $store->get('meta.api_version', 'v19.0');
        $formAny = $store->get('meta.form_ids') ?? $store->get('meta.form_id');
        $formIds = $this->normalizeFormIds($formAny);

        if (!$token || empty($formIds)) {
            throw new \RuntimeException(
                'Missing Meta settings. Need meta.access_token and meta.form_ids/meta.form_id in Admin → Settings.'
            );
        }

        $verify = env('CURL_CA_BUNDLE', true);

        $client = new Client([
            'base_uri' => "https://graph.facebook.com/{$apiVer}/",
            'timeout'  => 20,
            'verify'   => $verify,
        ]);

        $importedTotal = 0;

        foreach ($formIds as $formId) {
            // Best-effort lock to avoid overlapping for the same form
            $lock = null;
            try {
                $lock = Cache::lock("meta-import:{$companyId}:{$formId}", 55);
                if (method_exists($lock, 'get') && ! $lock->get()) {
                    $this->warn("Skipped form {$formId}: import already running.");
                    continue;
                }
            } catch (\Throwable $e) {
                // If store doesn’t support locks, proceed without blocking
            }

            try {
                $this->line("   → Fetching leads for form {$formId} …");

                $importedTotal += $this->fetchAllLeadsForForm(
                    $client,
                    $token,
                    $formId,
                    $limit,
                    function (array $lead) use ($companyId, $formId) {
                        $externalId   = $lead['id'];
                        $fieldDataArr = $lead['field_data'] ?? [];
                        $fields = [];
                        foreach ($fieldDataArr as $f) {
                            $fields[$f['name']] = $f['values'][0] ?? null;
                        }

                        Lead::updateOrCreate(
                            [
                                'company_id'      => $companyId,
                                'external_source' => 'meta',
                                'external_id'     => $externalId,
                            ],
                            [
                                'name'                 => $fields['full_name'] ?? $fields['name'] ?? null,
                                'email'                => $fields['email'] ?? null,
                                'phone'                => $fields['phone_number'] ?? null,
                                'status'               => 'New',
                                'source'               => 'Meta Lead Form',
                                'external_form_id'     => $formId,
                                'external_payload'     => $lead,
                                'external_received_at' => now(),
                            ]
                        );

                        $this->line("      Imported Meta lead {$externalId}");
                    }
                );

                $this->line("   ✔ Form {$formId}: cumulative imported {$importedTotal}.");
            } finally {
                try { optional($lock)->release(); } catch (\Throwable $e) { /* ignore */ }
            }
        }

        return $importedTotal;
    }

    /** Normalize meta.form_ids/meta.form_id into an array. */
    private function normalizeFormIds($value): array
    {
        if (empty($value)) return [];
        if (is_array($value)) return array_values(array_filter(array_map('trim', $value)));

        if (is_string($value)) {
            $t = trim($value);
            if ($t !== '' && str_starts_with($t, '[')) {
                $decoded = json_decode($t, true);
                if (is_array($decoded)) return array_values(array_filter(array_map('trim', $decoded)));
            }
            return array_values(array_filter(array_map('trim', explode(',', $t))));
        }

        return [trim((string) $value)];
    }

    /** Decrypt if the string looks like a Laravel Crypt payload; else return as-is. */
    private function decryptIfNeeded($value): ?string
    {
        if (!is_string($value) || $value === '') return $value;

        try {
            $decoded = json_decode(base64_decode($value), true);
            if (is_array($decoded) && isset($decoded['iv'], $decoded['value'], $decoded['mac'])) {
                return Crypt::decryptString($value);
            }
        } catch (\Throwable $e) {
            // treat as plain if decryption fails
        }
        return $value;
    }

    /**
     * Fetch all leads (handles pagination) and call $onLead for each.
     * Returns number of leads processed.
     */
    private function fetchAllLeadsForForm(Client $client, string $token, string $formId, int $limit, \Closure $onLead): int
    {
        $count = 0;
        $after = null;

        do {
            $query = [
                'access_token' => $token,
                'limit'        => $limit,
                'fields'       => 'id,created_time,field_data',
            ];
            if ($after) $query['after'] = $after;

            $resp    = $client->get("{$formId}/leads", ['query' => $query]);
            $payload = json_decode((string) $resp->getBody(), true);

            foreach ($payload['data'] ?? [] as $lead) {
                $onLead($lead);
                $count++;
            }

            $after = $payload['paging']['cursors']['after'] ?? null;
        } while ($after);

        return $count;
    }
}
