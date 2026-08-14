<?php

namespace App\Console\Commands;

use App\Models\System\Company;
use App\Models\User;
use App\Services\WhatsApp\History\HistoryImporter;
use App\Services\WhatsApp\History\HistoryIngestion;
use App\Services\WhatsApp\History\HistoryIntelligence;
use App\Services\WhatsApp\History\HistoryReview;
use App\Services\WhatsApp\History\HistoryTrackingPolicy;
use App\Support\Staging\StagingSafety;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class RunSyntheticWhatsAppHistoryUat extends Command
{
    protected $signature = 'staging:whatsapp-history-uat {--confirm : Run the rollback-only synthetic history rehearsal}';

    protected $description = 'Exercise WhatsApp history review/import on staging inside a transaction that is always rolled back';

    public function handle(
        StagingSafety $safety,
        HistoryIngestion $ingestion,
        HistoryIntelligence $intelligence,
        HistoryReview $review,
        HistoryImporter $importer,
        HistoryTrackingPolicy $tracking,
    ): int {
        if (! $this->option('confirm')) {
            $this->error('Refused: pass --confirm after reviewing the staging target.');

            return self::FAILURE;
        }

        $safety->assertRuntimeIsolated();
        if (app()->environment() !== 'staging') {
            throw new RuntimeException('Synthetic WhatsApp history UAT is restricted to APP_ENV=staging.');
        }
        $siteName = (string) env('WEBSITE_SITE_NAME', '');
        if ($siteName !== '' && $siteName !== 'app-sayaraforce-staging') {
            throw new RuntimeException('Synthetic WhatsApp history UAT refused an unexpected App Service identity.');
        }

        $company = Company::query()->where('email', 'tenant-a@staging.sayaraforce.test')->firstOrFail();
        $actor = User::query()->where('company_id', $company->id)->where('role', 'admin')->firstOrFail();
        $before = $this->operationalCounts((int) $company->id);
        $previousApiKey = config('services.openai.api_key');

        DB::beginTransaction();
        try {
            // Exercise the deterministic local evidence path. Real semantic
            // provider UAT is separately metered and never needed to prove the
            // quarantine/import invariants on the live staging database.
            config(['services.openai.api_key' => null]);
            $stamp = now()->utc()->format('YmdHis');
            $phones = [
                'customer' => '97150009'.substr($stamp, -4),
                'personal' => '97150109'.substr($stamp, -4),
                'unknown' => '97150209'.substr($stamp, -4),
            ];
            $payload = [
                'metadata' => ['phone_number_id' => 'synthetic-history-uat'],
                'threads' => [
                    $this->thread($phones['customer'], 'Synthetic Service Customer', [
                        'Could I have a quotation for an oil service?',
                        'How much will the service cost?',
                    ], $stamp.'-customer'),
                    $this->thread($phones['personal'], 'Synthetic Personal Contact', [
                        'Are you coming home for dinner this weekend?',
                    ], $stamp.'-personal'),
                    $this->thread($phones['unknown'], 'Synthetic Unknown Contact', ['Hi'], $stamp.'-unknown'),
                ],
            ];

            $batch = $ingestion->ingest($company, $payload, true);
            if ($batch->status !== 'awaiting_review' || $batch->candidates()->count() !== 3) {
                throw new RuntimeException('History discovery did not stop at the expected review quarantine.');
            }
            if ($this->operationalCounts((int) $company->id) !== $before) {
                throw new RuntimeException('History discovery mutated an operational CRM record.');
            }

            $candidates = $batch->candidates()->get()->mapWithKeys(function ($candidate) use ($intelligence): array {
                $candidate = $intelligence->analyse($candidate);

                return [ltrim((string) $candidate->phone_e164, '+') => $candidate];
            });
            $customer = $candidates->get($phones['customer']);
            $personal = $candidates->get($phones['personal']);
            if (! $customer || ! $personal || $customer->classification !== 'likely_customer') {
                throw new RuntimeException('Synthetic classification did not produce the reviewed customer candidate.');
            }

            $review->decide($customer, $actor, 'track');
            $imported = $importer->import($customer);
            if (! in_array($imported->import_status, ['created_client', 'matched_client'], true)) {
                throw new RuntimeException('Administrator-approved Track import did not complete.');
            }
            $review->decide($personal, $actor, 'dont_track');
            if ($tracking->decision($company, '+'.$phones['personal']) !== 'dont_track') {
                throw new RuntimeException("Don't Track did not become the future-ingress policy.");
            }
            if ($personal->fresh()->messages()->whereNull('purged_at')->exists()) {
                throw new RuntimeException("Don't Track did not purge the staged message bodies.");
            }

            $after = $this->operationalCounts((int) $company->id);
            foreach (['leads', 'opportunities', 'bookings', 'jobs', 'campaigns'] as $table) {
                if ($after[$table] !== $before[$table]) {
                    throw new RuntimeException("Historical review created an unauthorized {$table} record.");
                }
            }

            $result = [
                'status' => 'passed',
                'transaction' => 'rolled_back',
                'contacts_discovered' => 3,
                'review_state' => 'awaiting_admin_review',
                'tracked_import' => $imported->import_status,
                'dont_track_suppression' => true,
                'operational_actions_created' => 0,
                'outbound_messages_sent' => 0,
            ];
            DB::rollBack();
            config(['services.openai.api_key' => $previousApiKey]);
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            config(['services.openai.api_key' => $previousApiKey]);
            report($exception);
            $this->error('Synthetic WhatsApp history UAT failed without retaining any UAT records.');

            return self::FAILURE;
        }
    }

    /** @return array<string, int> */
    private function operationalCounts(int $companyId): array
    {
        return collect(['clients', 'leads', 'opportunities', 'bookings', 'jobs', 'campaigns'])
            ->mapWithKeys(fn (string $table): array => [
                $table => DB::table($table)->where('company_id', $companyId)->count(),
            ])->all();
    }

    /** @return array<string, mixed> */
    private function thread(string $phone, string $name, array $messages, string $prefix): array
    {
        return [
            'wa_id' => $phone,
            'profile' => ['name' => $name],
            'messages' => collect($messages)->values()->map(fn (string $body, int $index): array => [
                'id' => "{$prefix}-{$index}",
                'type' => 'text',
                'text' => ['body' => $body],
                'timestamp' => now()->subDays(90 - $index)->timestamp,
            ])->all(),
        ];
    }
}
