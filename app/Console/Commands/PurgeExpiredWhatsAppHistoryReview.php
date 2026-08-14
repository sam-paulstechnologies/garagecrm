<?php

namespace App\Console\Commands;

use App\Models\WhatsApp\WhatsAppHistoryCandidate;
use App\Models\WhatsApp\WhatsAppHistoryImportBatch;
use Illuminate\Console\Command;

class PurgeExpiredWhatsAppHistoryReview extends Command
{
    protected $signature = 'whatsapp:purge-expired-history-review {--company=} {--dry-run}';

    protected $description = 'Purge encrypted staged content for expired pending WhatsApp history reviews';

    public function handle(): int
    {
        $query = WhatsAppHistoryImportBatch::query()
            ->whereIn('status', ['awaiting_review', 'partially_failed'])
            ->whereNotNull('review_expires_at')
            ->where('review_expires_at', '<=', now())
            ->when($this->option('company'), fn ($builder, $company) => $builder->where('company_id', (int) $company));
        $count = 0;
        $query->eachById(function (WhatsAppHistoryImportBatch $batch) use (&$count): void {
            $candidates = WhatsAppHistoryCandidate::query()
                ->where('whatsapp_history_import_batch_id', $batch->id)
                ->where('review_decision', 'pending')
                ->get();
            $count += $candidates->count();
            if ($this->option('dry-run')) {
                return;
            }
            foreach ($candidates as $candidate) {
                $candidate->messages()->whereNull('purged_at')->get()->each(function ($message): void {
                    $message->forceFill([
                        'body' => null, 'metadata' => null, 'customer_identifier' => null, 'purged_at' => now(),
                    ])->save();
                });
                $candidate->forceFill(['staged_content_purged_at' => now(), 'import_status' => 'expired'])->save();
            }
            $batch->forceFill(['status' => 'completed', 'completed_at' => now()])->save();
        });

        $this->info(($this->option('dry-run') ? 'Would purge ' : 'Purged ').$count.' pending history candidates.');

        return self::SUCCESS;
    }
}
