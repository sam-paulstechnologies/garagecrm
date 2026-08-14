<?php

namespace App\Jobs;

use App\QuickScan\QuickScanWebhookProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessQuickScanWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $eventId)
    {
        $this->onConnection('database');
        $this->onQueue('default');
    }

    public function handle(QuickScanWebhookProcessor $processor): void
    {
        $processor->process($this->eventId);
    }
}
