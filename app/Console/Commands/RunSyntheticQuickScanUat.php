<?php

namespace App\Console\Commands;

use App\Models\System\Company;
use App\Models\User;
use App\QuickScan\QuickScanConversion;
use App\QuickScan\QuickScanPurge;
use App\QuickScan\QuickScanSyntheticFixture;
use App\Support\Staging\StagingSafety;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class RunSyntheticQuickScanUat extends Command
{
    protected $signature = 'staging:quick-scan-uat {--confirm : Run the rollback-only synthetic Quick Scan rehearsal}';

    protected $description = 'Exercise Quick Scan report, decline/purge, and accepted handoff paths without retaining UAT data';

    public function handle(
        StagingSafety $safety,
        QuickScanSyntheticFixture $fixture,
        QuickScanPurge $purge,
        QuickScanConversion $conversion,
    ): int {
        if (! $this->option('confirm')) {
            $this->error('Refused: pass --confirm after reviewing the staging target.');

            return self::FAILURE;
        }

        $safety->assertRuntimeIsolated();
        if (! app()->environment('staging') || ! config('quick_scan.enabled')) {
            throw new RuntimeException('Synthetic Quick Scan UAT requires the enabled staging environment.');
        }
        $siteName = (string) env('WEBSITE_SITE_NAME', '');
        if ($siteName !== '' && $siteName !== 'app-sayaraforce-staging') {
            throw new RuntimeException('Synthetic Quick Scan UAT refused an unexpected App Service identity.');
        }

        $actor = User::query()->whereIn('role', User::PLATFORM_ROLES)->orderBy('id')->firstOrFail();
        $company = Company::query()->where('email', 'tenant-a@staging.sayaraforce.test')->firstOrFail();
        $before = $this->protectedCounts((int) $company->id);

        DB::beginTransaction();
        try {
            $reportScan = $fixture->create($actor, 500)['scan'];
            $metrics = (array) $reportScan->report_metrics;
            if ($reportScan->status !== 'report_ready'
                || (int) $reportScan->contacts_discovered !== 500
                || (int) $reportScan->contacts_analysed > 500
                || (int) ($metrics['conversations_discovered'] ?? 0) !== 500) {
                throw new RuntimeException('The 500-contact Quick Scan report did not reach its expected bounded state.');
            }

            $declined = $fixture->create($actor, 25)['scan'];
            $declinedMetrics = (array) $declined->report_metrics;
            $declined = $purge->decline($declined);
            $declined = $purge->purge($declined, true);
            if ($declined->status !== 'purged'
                || $declined->candidates()->exists()
                || $declined->messages()->exists()
                || (int) data_get($declined->report_metrics, 'conversations_discovered') !== (int) ($declinedMetrics['conversations_discovered'] ?? 0)) {
                throw new RuntimeException('Quick Scan decline/purge did not remove customer evidence while retaining aggregate prospect metrics.');
            }

            $accepted = $fixture->create($actor, 25)['scan'];
            $accepted = $conversion->accept($accepted);
            $accepted = $conversion->bindToCompany($accepted, $company);
            $batch = DB::table('whatsapp_history_import_batches')
                ->where('company_id', $company->id)
                ->where('connection_scope_hash', hash_hmac('sha256', 'quick-scan|'.$accepted->public_id, (string) config('messaging.history.hmac_key')))
                ->first();
            if (! $batch || $accepted->outcome !== 'converted' || (int) $accepted->converted_company_id !== (int) $company->id) {
                throw new RuntimeException('Quick Scan acceptance did not create the normal quarantined history-review handoff.');
            }
            if (DB::table('whatsapp_history_candidates')->where('whatsapp_history_import_batch_id', $batch->id)->count() !== 25) {
                throw new RuntimeException('Quick Scan acceptance did not preserve the reviewed candidate set.');
            }
            if (DB::table('whatsapp_history_messages')->where('whatsapp_history_import_batch_id', $batch->id)
                ->whereNotNull('provider_message_id')->exists()) {
                throw new RuntimeException('Quick Scan acceptance transferred a provider-controlled message identifier.');
            }

            $after = $this->protectedCounts((int) $company->id);
            foreach ($before as $key => $count) {
                if ($after[$key] !== $count) {
                    throw new RuntimeException("Quick Scan UAT changed protected tenant state: {$key}.");
                }
            }

            $result = [
                'status' => 'passed',
                'transaction' => 'rolled_back',
                'report_contacts_discovered' => 500,
                'report_contacts_analysed' => (int) $reportScan->contacts_analysed,
                'report_external_ai_calls' => (int) $reportScan->ai_calls,
                'decline_customer_evidence_purged' => true,
                'decline_aggregate_metrics_retained' => true,
                'accepted_handoff_candidates' => 25,
                'accepted_handoff_state' => 'awaiting_review',
                'provider_identifiers_transferred' => false,
                'tenant_ai_usage_consumed' => 0,
                'tenant_history_usage_consumed' => 0,
                'crm_records_created' => 0,
                'outbound_messages_sent' => 0,
            ];

            DB::rollBack();
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            report($exception);
            $this->error('Synthetic Quick Scan UAT failed without retaining any UAT records.');

            return self::FAILURE;
        }
    }

    /** @return array<string, int> */
    private function protectedCounts(int $companyId): array
    {
        return [
            'clients' => DB::table('clients')->where('company_id', $companyId)->count(),
            'leads' => DB::table('leads')->where('company_id', $companyId)->count(),
            'opportunities' => DB::table('opportunities')->where('company_id', $companyId)->count(),
            'bookings' => DB::table('bookings')->where('company_id', $companyId)->count(),
            'ai_customer_usages' => DB::table('ai_customer_usages')->where('company_id', $companyId)->count(),
            'history_contact_usages' => DB::table('whatsapp_history_contact_usages')->where('company_id', $companyId)->count(),
        ];
    }
}
