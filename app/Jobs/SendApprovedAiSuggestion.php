<?php

namespace App\Jobs;

use App\Commercial\Jobs\EntitlementAwareJob;
use App\Commercial\Jobs\ExplicitlyApprovedEntitlementJob;
use App\Commercial\Jobs\RequireJobEntitlement;
use App\Commercial\OutboundEntitlementPolicy;
use App\Models\AiSuggestion;
use App\Models\MessageLog;
use App\Models\System\Company;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendApprovedAiSuggestion implements ShouldQueue, EntitlementAwareJob, ExplicitlyApprovedEntitlementJob
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $suggestionId)
    {
        $this->onConnection('database');
        $this->onQueue('notifications');
    }

    public function middleware(): array
    {
        return [new RequireJobEntitlement()];
    }

    public function entitlementCompanyId(): ?int
    {
        return AiSuggestion::query()->whereKey($this->suggestionId)->value('company_id');
    }

    public function entitlementCapability(): string
    {
        return 'ai_action_execution';
    }

    public function commercialActionApproved(): bool
    {
        return AiSuggestion::query()->whereKey($this->suggestionId)
            ->where('status', 'approved')
            ->whereNotNull('approved_by')
            ->exists();
    }

    public function handle(WhatsAppService $whatsApp, OutboundEntitlementPolicy $outbound): void
    {
        $suggestion = AiSuggestion::query()->findOrFail($this->suggestionId);
        abort_unless($this->commercialActionApproved(), 403);

        $inbound = MessageLog::query()
            ->where('company_id', $suggestion->company_id)
            ->findOrFail($suggestion->message_log_id);

        $company = Company::query()->findOrFail($inbound->company_id);
        $outbound->assertAllowed($company, OutboundEntitlementPolicy::MANUAL);

        $whatsApp->sendText((string) $inbound->from_number, (string) $suggestion->suggestion_text, [
            'company_id' => $company->id,
            'commercial_purpose' => OutboundEntitlementPolicy::MANUAL,
            'approved_ai_suggestion_id' => $suggestion->id,
        ]);
    }
}
