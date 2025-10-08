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
                            {--company= : Company ID (default: first company)}
                            {--limit=50 : Per-page fetch size}';

    protected $description = 'Import leads from Meta Lead Ads into GarageCRM';

    public function handle(): int
    {
        $this->info('Starting Meta lead import...');

        $companyId = (int)($this->option('company') ?: (Company::query()->value('id') ?? 0));
        if (!$companyId) {
            $this->error('No company found. Use --company=<id>.');
            return self::FAILURE;
        }

        $store  = new SettingsStore($companyId);
        $token  = $this->decryptIfNeeded($store->get('meta.access_token'));
        $apiVer = $store->get('meta.api_version', 'v19.0');
        $formAny = $store->get('meta.form_ids') ?? $store->get('meta.form_id');
        $formIds = $this->normalizeFormIds($formAny);

        if (!$token || empty($formIds)) {
            $this->error('Missing Meta settings. Need meta.access_token and meta.form_ids/meta.form_id in Admin → Settings.');
            return self::FAILURE;
        }

        $limit  = max(1, (int)$this->option('limit'));
        $verify = env('CURL_CA_BUNDLE', true);

        $client = new Client([
            'base_uri' => "https://graph.facebook.com/{$apiVer}/",
            'timeout'  => 20,
            'verify'   => $verify,
        ]);

        $total = 0;

        try {
            foreach ($formIds as $formId) {

                // 🧠 Prevent overlapping imports for same form
                $lock = Cache::lock("meta-import:{$companyId}:{$formId}", 55);
                if (! $lock->get()) {
                    $this->warn("Skipped form {$formId}: import already running.");
                    continue;
                }

                try {
                    $this->line("→ Fetching leads for form {$formId} ...");

                    $total += $this->fetchAllLeadsForForm($client, $token, $formId, $limit, function ($lead) use ($companyId, $formId) {
                        $externalId   = $lead['id'];
                        $createdAt    = $lead['created_time'] ?? null;
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

                        $this->line("   Imported Meta lead {$externalId}");
                    });

                    $this->line("✔ Form {$formId}: imported {$total} lead(s) so far.");
                } finally {
                    optional($lock)->release();
                }
            }

            $this->info("Meta import complete. Total imported: {$total}.");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error("Meta import failed: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /** Normalize meta.form_ids/meta.form_id into an array. */
    private function normalizeFormIds($value): array
    {
        if (empty($value)) return [];
        if (is_array($value)) return array_values(array_filter(array_map('trim', $value)));

        if (is_string($value)) {
            $t = trim($value);
            if ($t !== '' && $t[0] === '[') {
                $decoded = json_decode($t, true);
                if (is_array($decoded)) return array_values(array_filter(array_map('trim', $decoded)));
            }
            return array_values(array_filter(array_map('trim', explode(',', $t))));
        }

        return [trim((string)$value)];
    }

    /** If value looks like a Laravel Crypt payload, decrypt it; otherwise return as-is. */
    private function decryptIfNeeded($value): ?string
    {
        if (!is_string($value) || $value === '') return $value;

        try {
            $decoded = json_decode(base64_decode($value), true);
            if (is_array($decoded) && isset($decoded['iv'], $decoded['value'], $decoded['mac'])) {
                return Crypt::decryptString($value);
            }
        } catch (\Throwable $e) {
            // Treat as plain if decryption fails
        }
        return $value;
    }

    /** Fetch all leads for a form (handles pagination) and call $onLead for each. */
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
            $payload = json_decode((string)$resp->getBody(), true);

            foreach ($payload['data'] ?? [] as $lead) {
                $onLead($lead);
                $count++;
            }
            $after = $payload['paging']['cursors']['after'] ?? null;

        } while ($after);

        return $count;
    }
}
