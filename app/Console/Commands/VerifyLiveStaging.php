<?php

namespace App\Console\Commands;

use App\Support\Staging\StagingSafety;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class VerifyLiveStaging extends Command
{
    protected $signature = 'staging:verify-live {--json}';

    protected $description = 'Verify the live staging schema, synthetic data, and provider isolation';

    public function handle(StagingSafety $safety): int
    {
        try {
            $safety->assertRuntimeIsolated();
            $database = (string) DB::connection()->getDatabaseName();
            $baseTables = (int) DB::table('information_schema.tables')
                ->where('table_schema', $database)->where('table_type', 'BASE TABLE')->count();
            $views = (int) DB::table('information_schema.tables')
                ->where('table_schema', $database)->where('table_type', 'VIEW')->count();

            $this->assertSame(118, $baseTables, 'base-table count');
            $this->assertSame(2, $views, 'view count');

            $messagingTables = [
                'messaging_connections', 'messaging_phone_numbers', 'messaging_onboarding_sessions',
                'messaging_consents', 'messaging_connection_checks', 'messaging_audit_logs',
                'messaging_webhook_events',
            ];
            foreach ($messagingTables as $table) {
                if (! Schema::hasTable($table)) {
                    throw new RuntimeException("Missing messaging table: {$table}.");
                }
            }

            $commercialTables = [
                'plan_versions', 'prices', 'plan_entitlements', 'subscriptions',
                'company_entitlement_overrides', 'entitlement_usages',
                'entitlement_audit_logs', 'billing_provider_events',
            ];
            foreach ($commercialTables as $table) {
                if (! Schema::hasTable($table)) {
                    throw new RuntimeException("Missing commercial foundation table: {$table}.");
                }
            }

            foreach (['company_id', 'booking_id', 'client_id', 'description', 'status'] as $column) {
                if (! Schema::hasColumn('jobs', $column)) {
                    throw new RuntimeException("Operational jobs column is missing: {$column}.");
                }
            }
            foreach (['queue', 'payload', 'attempts', 'available_at'] as $column) {
                if (! Schema::hasColumn('queue_jobs', $column)) {
                    throw new RuntimeException("Queue storage column is missing: {$column}.");
                }
            }

            $tenantCount = (int) DB::table('companies')->count();
            $userCount = (int) DB::table('users')->count();
            $this->assertAtLeast(2, $tenantCount, 'synthetic tenant count');
            $this->assertSame(0, (int) DB::table('companies')
                ->where(fn ($query) => $query->whereNull('email')->orWhere('email', 'not like', '%@staging.sayaraforce.test'))
                ->count(), 'non-synthetic tenant count');
            $this->assertAtLeast(4, $userCount, 'synthetic user count');
            $this->assertSame(5, (int) DB::table('plans')
                ->whereIn('code', ['free', 'service', 'growth', 'performance', 'ai_pro'])->count(), 'canonical plan count');
            $this->assertSame($tenantCount, (int) DB::table('subscriptions')->count(), 'explicit subscription count');
            $this->assertSame(1, (int) DB::table('subscriptions as s')
                ->join('companies as c', 'c.id', '=', 's.company_id')
                ->join('plan_versions as pv', 'pv.id', '=', 's.plan_version_id')
                ->join('plans as p', 'p.id', '=', 'pv.plan_id')
                ->where('c.email', 'tenant-a@staging.sayaraforce.test')->where('p.code', 'ai_pro')->count(), 'primary synthetic plan mapping');
            $this->assertSame(1, (int) DB::table('subscriptions as s')
                ->join('companies as c', 'c.id', '=', 's.company_id')
                ->join('plan_versions as pv', 'pv.id', '=', 's.plan_version_id')
                ->join('plans as p', 'p.id', '=', 'pv.plan_id')
                ->where('c.email', 'tenant-b@staging.sayaraforce.test')->where('p.code', 'service')->count(), 'secondary synthetic plan mapping');
            $this->assertSame(0, (int) DB::table('users')
                ->where(fn ($query) => $query->whereNull('email')->orWhere('email', 'not like', '%@staging.sayaraforce.test'))
                ->count(), 'non-synthetic user count');
            foreach (['clients', 'leads', 'vehicles', 'conversations'] as $table) {
                $this->assertSame(2, (int) DB::table($table)->count(), "{$table} synthetic row count");
            }

            $this->assertSame(0, (int) DB::table('companies')
                ->whereNotNull('meta_access_token')->orWhereNotNull('meta_waba_id')->orWhereNotNull('meta_phone_number_id')
                ->count(), 'legacy provider credential count');
            foreach (array_merge($messagingTables, [
                'whatsapp_connect_sessions', 'whatsapp_connection_audits', 'whatsapp_history_messages',
                'whatsapp_messages', 'whatsapp_synced_contacts', 'whatsapp_webhook_events',
            ]) as $table) {
                $this->assertSame(0, (int) DB::table($table)->count(), "{$table} provider row count");
            }

            DB::select('SELECT * FROM vw_ai_metrics_daily LIMIT 1');
            DB::select('SELECT * FROM vw_journey_summary LIMIT 1');

            $result = [
                'status' => 'passed',
                'environment' => 'staging',
                'base_tables' => $baseTables,
                'views' => $views,
                'messaging_tables' => count($messagingTables),
                'commercial_tables' => count($commercialTables),
                'synthetic_tenants' => $tenantCount,
                'synthetic_users' => $userCount,
                'provider_records' => 0,
                'jobs_table' => 'operational',
                'queue_jobs_table' => 'database_queue',
            ];

            $this->line($this->option('json') ? json_encode($result, JSON_THROW_ON_ERROR) : 'Live staging verification passed.');

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error('Live staging verification failed: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function assertSame(int $expected, int $actual, string $label): void
    {
        if ($actual !== $expected) {
            throw new RuntimeException("Unexpected {$label}: expected {$expected}, found {$actual}.");
        }
    }

    private function assertAtLeast(int $minimum, int $actual, string $label): void
    {
        if ($actual < $minimum) {
            throw new RuntimeException("Unexpected {$label}: expected at least {$minimum}, found {$actual}.");
        }
    }
}
