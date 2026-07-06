<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SystemHealthController extends SuperAdminController
{
    public function __invoke()
    {
        $checks = $this->healthChecks();

        $failedJobs = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->latest('failed_at')->limit(10)->get()
            : collect();

        $lastRecords = [
            'last_message' => $this->tableLatest('message_logs'),
            'last_lead' => $this->tableLatest('leads'),
            'last_booking' => $this->tableLatest('bookings'),
            'last_job' => $this->tableLatest('jobs'),
            'last_invoice' => $this->tableLatest('invoices'),
        ];

        return view('super_admin.system.health', [
            'checks' => $checks,
            'failedJobs' => $failedJobs,
            'failedJobsCount' => $this->countRows('failed_jobs'),
            'queueConnection' => Config::get('queue.default'),
            'cacheStore' => Config::get('cache.default'),
            'environment' => app()->environment(),
            'lastRecords' => $lastRecords,
        ]);
    }
}
