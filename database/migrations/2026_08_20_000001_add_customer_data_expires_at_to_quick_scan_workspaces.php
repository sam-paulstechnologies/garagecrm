<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fable final remediation (M2): canonical Quick Scan customer-data retention
 * deadline.
 *
 * Previously a scan could ingest customer history and then stall/fail before
 * report_ready, leaving both `report_expires_at` and `purge_scheduled_at` NULL
 * so no retention bucket ever selected it — customer PII could be retained
 * indefinitely. `customer_data_expires_at` is the single, always-set outer
 * bound after which any non-converted scan holding customer data is swept.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('quick_scan_workspaces', 'customer_data_expires_at')) {
            Schema::table('quick_scan_workspaces', function (Blueprint $table): void {
                $table->timestamp('customer_data_expires_at')->nullable()->after('report_expires_at')->index();
            });
        }

        // Backfill every existing scan that could still hold customer-level data
        // (anything not already purged and not converted) with a deterministic
        // non-null deadline so it can never be retained forever.
        $retentionHours = max(1, (int) config('quick_scan.customer_data_retention_hours', 96));

        DB::table('quick_scan_workspaces')
            ->whereNull('customer_data_expires_at')
            ->where('status', '!=', 'purged')
            ->whereNull('converted_company_id')
            ->orderBy('id')
            ->chunkById(500, function ($scans) use ($retentionHours): void {
                foreach ($scans as $scan) {
                    $anchor = $scan->history_sync_started_at
                        ?? $scan->connected_at
                        ?? $scan->created_at
                        ?? now();

                    $deadline = Carbon::parse($anchor)->addHours($retentionHours);

                    if (! empty($scan->report_expires_at)) {
                        $reportExpiry = Carbon::parse($scan->report_expires_at);
                        if ($reportExpiry->greaterThan($deadline)) {
                            $deadline = $reportExpiry;
                        }
                    }

                    DB::table('quick_scan_workspaces')
                        ->where('id', $scan->id)
                        ->update(['customer_data_expires_at' => $deadline]);
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('quick_scan_workspaces', 'customer_data_expires_at')) {
            Schema::table('quick_scan_workspaces', function (Blueprint $table): void {
                $table->dropColumn('customer_data_expires_at');
            });
        }
    }
};
