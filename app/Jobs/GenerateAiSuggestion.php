<?php

namespace App\Jobs;

use App\Commercial\Jobs\EntitlementAwareJob;
use App\Commercial\Jobs\RequireJobEntitlement;
use App\Models\MessageLog;
use App\Services\Chat\SuggestReplyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateAiSuggestion implements ShouldQueue, EntitlementAwareJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $messageId) {}

    public function middleware(): array
    {
        return [new RequireJobEntitlement()];
    }

    public function entitlementCompanyId(): ?int
    {
        return MessageLog::query()->whereKey($this->messageId)->value('company_id');
    }

    public function entitlementCapability(): string
    {
        return 'ai_recommendations';
    }

    public function handle(SuggestReplyService $replyService): void
    {
        $message = MessageLog::query()->find($this->messageId);

        if (! $message || $message->direction !== 'in' || ! $message->company_id) {
            return;
        }

        // A recommendation is observational. It must never create or mutate
        // an Opportunity merely because a customer sent an inbound message.
        $leadContext = optional($message->lead)->toArray() ?? [];
        $replyService->generateFor($message, $leadContext);
    }
}
