<?php

namespace App\Console\Commands;

use App\Models\Client\Client;
use App\Models\QuickScan\QuickScanMessage;
use App\Models\QuickScan\QuickScanProviderSession;
use App\Models\QuickScan\QuickScanWorkspace;
use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Models\WhatsApp\WhatsAppHistoryMessage;
use App\Models\WhatsApp\WhatsAppSyncedContact;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

/**
 * Fable final remediation (backup/restore evidence, GAP-021).
 *
 * Database restore success is NOT application recovery success: encrypted
 * fields are only usable if the restored app can decrypt them with the restored
 * APP_KEY / Key Vault material. This READ-ONLY command samples representative
 * application-encrypted fields (provider tokens, WhatsApp/history content, Quick
 * Scan PII) plus ordinary CRM records and reports how many decrypt successfully.
 *
 * It NEVER prints any decrypted value — only pass/fail counts and byte lengths —
 * and refuses to run against a production database host. Run it on the
 * authorized staging network against the isolated restored staging database to
 * capture witnessed decryption evidence.
 */
class VerifyEncryptedFieldRecovery extends Command
{
    protected $signature = 'staging:verify-encrypted-recovery {--sample=5 : Records to sample per model} {--json= : Optional path to write the evidence JSON}';

    protected $description = 'Verify application-encrypted fields decrypt after a database restore (read-only, no plaintext output).';

    /** @var array<int,class-string<Model>> */
    private const ENCRYPTED_MODELS = [
        QuickScanProviderSession::class, // encrypted provider token/asset ids
        QuickScanWorkspace::class,       // Quick Scan encrypted garage PII
        QuickScanMessage::class,         // Quick Scan encrypted message bodies
        WhatsAppHistoryMessage::class,   // WhatsApp history encrypted content
        WhatsAppHistoryCandidate::class, // WhatsApp history encrypted identity
        WhatsAppSyncedContact::class,    // WhatsApp synced-contact encrypted PII
    ];

    public function handle(): int
    {
        $this->assertNotProductionHost();

        $sample = max(1, min(50, (int) $this->option('sample')));
        $connection = (string) config('database.default');

        $evidence = [
            'schema' => 'sayaraforce-encrypted-recovery/v1',
            'database_connection' => $connection,
            'database_name' => (string) config("database.connections.{$connection}.database"),
            'app_key_fingerprint' => substr(hash('sha256', (string) config('app.key')), 0, 12),
            'environment' => app()->environment(),
            'sample_per_model' => $sample,
            'models' => [],
            'ordinary_records_readable' => false,
            'decrypted_ok' => 0,
            'decrypt_failed' => 0,
            'result' => 'unknown',
        ];

        foreach (self::ENCRYPTED_MODELS as $class) {
            $evidence['models'][] = $this->verifyModel($class, $sample, $evidence);
        }

        // Ordinary (non-encrypted) CRM records must also be readable post-restore.
        try {
            Client::query()->limit($sample)->get()->each(fn (Client $client) => $client->getAttribute('name'));
            $evidence['ordinary_records_readable'] = true;
        } catch (\Throwable) {
            $evidence['ordinary_records_readable'] = false;
        }

        $evidence['result'] = ($evidence['decrypt_failed'] === 0 && $evidence['ordinary_records_readable'])
            ? 'pass'
            : 'fail';

        $this->renderTable($evidence);

        if ($path = $this->option('json')) {
            @file_put_contents((string) $path, json_encode($evidence, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
            $this->line('Evidence written to '.$path);
        }

        return $evidence['result'] === 'pass' ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  class-string<Model>  $class
     * @param  array<string,mixed>  $evidence
     * @return array<string,mixed>
     */
    private function verifyModel(string $class, int $sample, array &$evidence): array
    {
        $model = new $class;
        $encryptedAttributes = collect($model->getCasts())
            ->filter(fn ($cast): bool => is_string($cast) && str_starts_with($cast, 'encrypted'))
            ->keys();

        $ok = 0;
        $failed = 0;
        $bytes = 0;

        $records = $class::query()->orderByDesc($model->getKeyName())->limit($sample)->get();
        foreach ($records as $record) {
            foreach ($encryptedAttributes as $attribute) {
                if (blank($record->getRawOriginal($attribute))) {
                    continue;
                }
                try {
                    $value = $record->getAttribute($attribute);
                    $ok++;
                    $bytes += strlen(is_array($value) ? (string) json_encode($value) : (string) $value);
                } catch (\Throwable) {
                    $failed++;
                }
            }
        }

        $evidence['decrypted_ok'] += $ok;
        $evidence['decrypt_failed'] += $failed;

        return [
            'model' => class_basename($class),
            'encrypted_attributes' => $encryptedAttributes->values()->all(),
            'records_sampled' => $records->count(),
            'decrypted_ok' => $ok,
            'decrypt_failed' => $failed,
            'decrypted_bytes' => $bytes,
        ];
    }

    private function assertNotProductionHost(): void
    {
        $connection = (string) config('database.default');
        $host = strtolower(trim((string) config("database.connections.{$connection}.host")));
        $denylist = array_filter(array_map(
            fn ($value): string => strtolower(trim((string) $value)),
            explode(',', (string) config('staging.production.database_hosts'))
        ));

        if ($host !== '' && in_array($host, $denylist, true)) {
            throw new RuntimeException('Refused: the current database host is on the production denylist.');
        }
    }

    /**
     * @param  array<string,mixed>  $evidence
     */
    private function renderTable(array $evidence): void
    {
        $this->info(sprintf(
            'Encrypted-field recovery: %s (decrypted_ok=%d, failed=%d, ordinary_readable=%s, db=%s)',
            strtoupper((string) $evidence['result']),
            $evidence['decrypted_ok'],
            $evidence['decrypt_failed'],
            $evidence['ordinary_records_readable'] ? 'yes' : 'no',
            $evidence['database_name'],
        ));

        $this->table(
            ['Model', 'Encrypted attrs', 'Sampled', 'OK', 'Failed'],
            collect($evidence['models'])->map(fn (array $row): array => [
                $row['model'],
                (string) count($row['encrypted_attributes']),
                (string) $row['records_sampled'],
                (string) $row['decrypted_ok'],
                (string) $row['decrypt_failed'],
            ])->all(),
        );
    }
}
