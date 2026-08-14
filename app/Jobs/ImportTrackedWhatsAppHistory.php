<?php

namespace App\Jobs;

use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Models\WhatsApp\WhatsAppHistoryImportBatch;
use App\Services\WhatsApp\History\HistoryBatchCounters;
use App\Services\WhatsApp\History\HistoryImporter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportTrackedWhatsAppHistory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [15, 90, 300];

    public function __construct(
        public readonly int $batchId,
        public readonly int $companyId,
        public readonly int $afterId = 0,
    ) {
        $this->onConnection('database');
        $this->onQueue('default');
    }

    public function handle(HistoryImporter $importer, HistoryBatchCounters $counters): void
    {
        $batch = WhatsAppHistoryImportBatch::query()
            ->where('company_id', $this->companyId)
            ->find($this->batchId);
        if (! $batch) {
            return;
        }
        $batch->forceFill(['status' => 'importing', 'last_error_code' => null])->save();
        $candidates = WhatsAppHistoryCandidate::query()
            ->where('company_id', $this->companyId)
            ->where('whatsapp_history_import_batch_id', $batch->id)
            ->where('review_decision', 'track')
            ->where('id', '>', $this->afterId)
            ->orderBy('id')
            ->limit(50)
            ->get();

        foreach ($candidates as $candidate) {
            try {
                $importer->import($candidate);
            } catch (\Throwable) {
                $candidate->forceFill(['import_status' => 'failed'])->save();
            }
        }

        $counters->refresh($batch);
        if ($candidates->count() === 50) {
            self::dispatch($batch->id, $this->companyId, (int) $candidates->last()->id);

            return;
        }

        $failed = $batch->candidates()->where('import_status', 'failed')->exists();
        $batch->forceFill([
            'status' => $failed ? 'partially_failed' : 'completed',
            'completed_at' => now(),
        ])->save();
    }
}
