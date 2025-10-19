<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TestQueueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $who;
    public string $marker;

    /**
     * Create a new job instance.
     */
    public function __construct(string $who = 'TestUser', ?string $marker = null)
    {
        $this->who = $who;
        $this->marker = $marker ?? now()->toDateTimeString();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        usleep(250_000); // simulate work for 0.25s

        Log::info('[TestQueueJob] ✅ Job executed', [
            'who'    => $this->who,
            'marker' => $this->marker,
            'time'   => now()->toDateTimeString(),
            'queue'  => $this->queue,
        ]);
    }
}
