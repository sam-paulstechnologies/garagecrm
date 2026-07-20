<?php

namespace App\Jobs\PlatformMarketing;

use App\Models\PlatformMarketing\PlatformMarketingConversation;
use App\Models\PlatformMarketing\PlatformMarketingConversationMessage;
use App\Models\PlatformMarketing\PlatformMarketingCampaignRecipient;
use App\Models\PlatformMarketing\PlatformMarketingChannel;
use App\Models\PlatformMarketing\PlatformMarketingProspect;
use App\Services\PlatformMarketing\Ai\PlatformSalesAgent;
use App\Services\PlatformMarketing\PlatformComplianceService;
use App\Services\PlatformMarketing\PlatformWhatsAppSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPlatformInboundMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public array $payload, public array $context)
    {
        $this->onConnection('database')->onQueue('platform-marketing-high');
    }

    public function handle(
        PlatformComplianceService $compliance,
        PlatformSalesAgent $agent,
        PlatformWhatsAppSender $sender
    ): void {
        $prospect = PlatformMarketingProspect::query()->findOrFail($this->context['prospect_id']);
        $channel = PlatformMarketingChannel::query()->findOrFail($this->context['channel_id']);
        $recipient = isset($this->context['recipient_id'])
            ? PlatformMarketingCampaignRecipient::query()->find($this->context['recipient_id'])
            : null;
        $conversation = isset($this->context['conversation_id'])
            ? PlatformMarketingConversation::query()->find($this->context['conversation_id'])
            : null;
        $message = data_get($this->payload, 'entry.0.changes.0.value.messages.0', []);
        $body = $this->extractBody($message);

        $conversation = $conversation instanceof PlatformMarketingConversation
            ? $conversation
            : PlatformMarketingConversation::query()->firstOrCreate(
                ['prospect_id' => $prospect->id, 'channel_id' => $channel->id],
                ['campaign_id' => $recipient?->campaign_id, 'state' => 'greeting']
            );

        PlatformMarketingConversationMessage::query()->create([
            'conversation_id' => $conversation->id,
            'prospect_id' => $prospect->id,
            'campaign_id' => $recipient?->campaign_id,
            'direction' => 'in',
            'actor' => 'prospect',
            'body' => $body,
            'provider_message_id' => $message['id'] ?? null,
            'provider_status' => 'received',
            'received_at' => now(),
            'meta' => ['provider' => 'meta'],
        ]);

        $channel->forceFill(['last_inbound_at' => now(), 'webhook_health' => 'receiving'])->save();
        $prospect->forceFill(['status' => 'replied'])->save();

        if ($compliance->isStopMessage($body)) {
            $compliance->optOut($prospect);
            return;
        }

        if (! $conversation->ai_enabled || $conversation->human_takeover) {
            $conversation->forceFill(['last_message_at' => now(), 'unread_count' => $conversation->unread_count + 1])->save();
            return;
        }

        $reply = $agent->respond($conversation, $body);
        $sendResult = $sender->sendText($channel, $prospect->normalized_phone, $reply['body']);

        PlatformMarketingConversationMessage::query()->create([
            'conversation_id' => $conversation->id,
            'prospect_id' => $prospect->id,
            'campaign_id' => $recipient?->campaign_id,
            'direction' => 'out',
            'actor' => $reply['source'] === 'openai' ? 'ai' : 'system',
            'body' => $reply['body'],
            'provider_message_id' => data_get($sendResult, 'messages.0.id'),
            'provider_status' => 'sent',
            'sent_at' => now(),
            'meta' => ['provider' => 'meta', 'ai_source' => $reply['source']],
        ]);

        $conversation->forceFill([
            'state' => $reply['state'] ?? $conversation->state,
            'qualification_status' => $reply['qualification_status'] ?? $conversation->qualification_status,
            'last_message_at' => now(),
        ])->save();
    }

    private function extractBody(array $message): string
    {
        return trim((string) (
            $message['text']['body']
            ?? $message['button']['text']
            ?? $message['interactive']['button_reply']['title']
            ?? $message['interactive']['list_reply']['title']
            ?? '[Non-text message received]'
        ));
    }
}
