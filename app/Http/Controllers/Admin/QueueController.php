<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Controllers\Controller;

class QueueController extends Controller
{
    /** Pick the actual jobs table name gracefully: queue_jobs or jobs */
    protected function jobsTable(): ?string
    {
        if (Schema::hasTable('queue_jobs')) return 'queue_jobs';
        if (Schema::hasTable('jobs')) return 'jobs';
        return null;
    }

    public function index(Request $request)
    {
        $jobsTable = $this->jobsTable();
        $now = now()->timestamp;

        $metrics = [
            'has_jobs_table' => (bool) $jobsTable,
            'queued'   => 0,
            'reserved' => 0,
            'delayed'  => 0,
            'total'    => 0,
            'failed'   => Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
        ];

        if ($jobsTable) {
            $q = DB::table($jobsTable);
            $metrics['total'] = (clone $q)->count();
            $metrics['queued'] = (clone $q)
                ->whereNull('reserved_at')
                ->where('available_at', '<=', $now)
                ->count();

            $metrics['reserved'] = (clone $q)
                ->whereNotNull('reserved_at')
                ->where('reserved_at', '<=', $now)
                ->count();

            $metrics['delayed'] = (clone $q)
                ->where('available_at', '>', $now)
                ->count();
        }

        $failedJobs = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->orderByDesc('id')->limit(20)->get()
            : collect();

        return view('admin.queue.index', compact('metrics', 'failedJobs', 'jobsTable'));
    }

    public function retry($id)
    {
        Artisan::call('queue:retry', ['id' => $id]);
        return back()->with('status', "Retried failed job #{$id}.");
    }

    public function retryAll()
    {
        Artisan::call('queue:retry', ['id' => 'all']);
        return back()->with('status', 'Retried all failed jobs.');
    }

    public function forget($id)
    {
        Artisan::call('queue:forget', ['id' => $id]);
        return back()->with('status', "Removed failed job #{$id} from the list.");
    }

    public function flush()
    {
        Artisan::call('queue:flush');
        return back()->with('status', 'Flushed all failed jobs.');
    }
}
