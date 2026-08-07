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

            $this->assertSame(110, $baseTables, 'base-table count');
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

            $this->assertSame(2, (int) DB::table('companies')->count(), 'synthetic tenant count');
            $this->assertSame(0, (int) DB::table('companies')
                ->where(fn ($query) => $query->whereNull('email')->orWhere('email', 'not like', '%@staging.sayaraforce.test'))
                ->count(), 'non-synthetic tenant count');
            $this->assertSame(4, (int) DB::table('users')->count(), 'synthetic user count');
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
                'synthetic_tenants' => 2,
                'synthetic_users' => 4,
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
}
