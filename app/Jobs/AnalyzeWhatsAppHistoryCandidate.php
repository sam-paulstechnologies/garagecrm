<?php

namespace App\Jobs;

use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Services\WhatsApp\History\HistoryIntelligence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeWhatsAppHistoryCandidate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $candidateId)
    {
        $this->onConnection('database');
        $this->onQueue('default');
    }

    public function handle(HistoryIntelligence $intelligence): void
    {
        $candidate = WhatsAppHistoryCandidate::query()->find($this->candidateId);
        if (! $candidate || in_array($candidate->review_decision, ['dont_track'], true)) {
            return;
        }

        $intelligence->analyse($candidate);
    }

    public function failed(): void
    {
        WhatsAppHistoryCandidate::query()->whereKey($this->candidateId)->update([
            'intelligence_status' => 'analysis_failed', 'updated_at' => now(),
        ]);
    }
}
