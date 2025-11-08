<?php

namespace App\Jobs;

use App\Models\MessageLog;
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

    public function handle(SuggestReplyService $svc): void
    {
        $msg = MessageLog::find($this->messageId);
        if (!$msg || $msg->direction !== 'in') return;

        // optional: load lead array for better prompts
        $lead = optional($msg->lead)->toArray() ?? [];

        $svc->generateFor($msg, $lead);
    }
}
