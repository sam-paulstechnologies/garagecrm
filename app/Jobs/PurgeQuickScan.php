<?php

namespace App\Jobs;

use App\Models\QuickScan\QuickScanWorkspace;
use App\QuickScan\QuickScanPurge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PurgeQuickScan implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $scanId)
    {
        $this->onConnection('database');
        $this->onQueue('default');
    }

    public function handle(QuickScanPurge $purge): void
    {
        $scan = QuickScanWorkspace::query()->find($this->scanId);
        if ($scan) {
            $purge->purge($scan);
        }
    }
}
