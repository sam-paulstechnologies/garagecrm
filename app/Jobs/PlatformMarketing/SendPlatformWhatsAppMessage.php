<?php

namespace App\Jobs\PlatformMarketing;

use App\Models\PlatformMarketing\PlatformMarketingCampaignRecipient;
use App\Models\PlatformMarketing\PlatformMarketingChannel;
use App\Models\PlatformMarketing\PlatformMarketingConversation;
use App\Models\PlatformMarketing\PlatformMarketingConversationMessage;
use App\Services\PlatformMarketing\PlatformWhatsAppSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPlatformWhatsAppMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $recipientId)
    {
        $this->onConnection('database')->onQueue('platform-marketing');
    }

    public function handle(PlatformWhatsAppSender $sender): void
    {
        $recipient = PlatformMarketingCampaignRecipient::query()
            ->with(['campaign', 'prospect'])
            ->findOrFail($this->recipientId);

        if ($recipient->status !== 'queued') {
            return;
        }

        $channel = PlatformMarketingChannel::query()
            ->where('is_active', true)
            ->where('connection_status', 'connected')
            ->firstOrFail();

        $body = $recipient->campaign->template_name
            ? "Template {$recipient->campaign->template_name} for {$recipient->prospect->contact_name}"
            : "Hi, this is PaulsTechnologies with SayaraForce.";

        $result = $sender->sendText($channel, $recipient->normalized_phone, $body);
        $providerId = data_get($result, 'messages.0.id');

        $conversation = PlatformMarketingConversation::query()->firstOrCreate(
            [
                'prospect_id' => $recipient->prospect_id,
                'campaign_id' => $recipient->campaign_id,
                'channel_id' => $channel->id,
            ],
            ['state' => 'greeting', 'qualification_status' => 'contacted']
        );

        PlatformMarketingConversationMessage::query()->create([
            'conversation_id' => $conversation->id,
            'prospect_id' => $recipient->prospect_id,
            'campaign_id' => $recipient->campaign_id,
            'direction' => 'out',
            'actor' => 'campaign_job',
            'message_type' => 'template',
            'body' => $body,
            'provider_message_id' => $providerId,
            'provider_status' => 'sent',
            'sent_at' => now(),
            'meta' => ['provider' => 'meta', 'response' => $result],
        ]);

        $recipient->forceFill([
            'status' => 'sent',
            'sent_at' => now(),
        ])->save();

        $conversation->forceFill(['last_message_at' => now()])->save();
        $recipient->prospect->forceFill(['status' => 'contacted', 'last_contacted_at' => now()])->save();
    }
}
