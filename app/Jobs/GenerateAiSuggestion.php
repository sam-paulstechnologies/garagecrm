<?php

namespace App\Jobs;

use App\Models\MessageLog;
use App\Models\Client\Client;
use App\Models\Client\Opportunity;
use App\Services\Ai\OpportunityFieldMapper;
use App\Services\Opportunities\OpportunityCompletionService;
use App\Services\Chat\SuggestReplyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAiSuggestion implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $messageId) {}

    public function handle(
        SuggestReplyService $replyService,
        OpportunityFieldMapper $fieldMapper,
        OpportunityCompletionService $completionService
    ): void {
        $msg = MessageLog::find($this->messageId);

        // Safety checks
        if (!$msg || $msg->direction !== 'in' || !$msg->company_id) {
            return;
        }

        /** -------------------------------------------------
         * Resolve Client
         * -------------------------------------------------- */
        $client = Client::where('company_id', $msg->company_id)
            ->where('phone', $msg->from_number)
            ->first();

        if (!$client) {
            // No client → no opportunity → no AI qualification
            return;
        }

        /** -------------------------------------------------
         * Find or create Opportunity (AI-owned)
         * -------------------------------------------------- */
        $opportunity = Opportunity::where('client_id', $client->id)
            ->where('company_id', $client->company_id)
            ->latest('id')
            ->first();

        if (!$opportunity) {
            $opportunity = Opportunity::create([
                'client_id'  => $client->id,
                'company_id' => $client->company_id,
                'title'      => 'AI Service Inquiry',
                'stage'      => 'new',        // sales pipeline untouched
                'ai_status'  => 'new',        // AI qualification
                'source'     => 'ai_chat',
            ]);
        }

        /** -------------------------------------------------
         * Extract structured fields from chat message
         * -------------------------------------------------- */
        $fields = $fieldMapper->extract($msg->body);

        if (!empty($fields)) {
            $opportunity->update($fields);
        }

        /** -------------------------------------------------
         * Sync AI qualification status
         * -------------------------------------------------- */
        $completionService->syncStatus($opportunity);

        /** -------------------------------------------------
         * Continue existing AI reply flow
         * -------------------------------------------------- */
        $leadContext = optional($msg->lead)->toArray() ?? [];
        $replyService->generateFor($msg, $leadContext);
    }
}