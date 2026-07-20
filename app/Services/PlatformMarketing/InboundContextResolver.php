<?php

namespace App\Services\PlatformMarketing;

use App\Models\PlatformMarketing\PlatformMarketingCampaignRecipient;
use App\Models\PlatformMarketing\PlatformMarketingChannel;
use App\Models\PlatformMarketing\PlatformMarketingConversation;
use App\Models\PlatformMarketing\PlatformMarketingProspect;

class InboundContextResolver
{
    public function __construct(private PlatformPhoneNormalizer $phoneNormalizer)
    {
    }

    public function resolve(array $value): ?array
    {
        $phoneNumberId = $value['metadata']['phone_number_id'] ?? null;
        $from = $value['messages'][0]['from'] ?? null;

        if (blank($phoneNumberId) || blank($from)) {
            return null;
        }

        $channel = PlatformMarketingChannel::query()
            ->where('phone_number_id', trim((string) $phoneNumberId))
            ->where('is_active', true)
            ->first();

        if (! $channel) {
            return null;
        }

        $normalizedPhone = $this->phoneNormalizer->normalize($from);

        $prospect = PlatformMarketingProspect::query()
            ->where('normalized_phone', $normalizedPhone)
            ->first();

        if (! $prospect) {
            return null;
        }

        $recipient = PlatformMarketingCampaignRecipient::query()
            ->where('prospect_id', $prospect->id)
            ->whereIn('status', ['sent', 'delivered', 'read', 'replied'])
            ->latest('id')
            ->first();

        $conversation = PlatformMarketingConversation::query()
            ->where('prospect_id', $prospect->id)
            ->where('channel_id', $channel->id)
            ->latest('last_message_at')
            ->latest('id')
            ->first();

        if (! $recipient && ! $conversation) {
            return null;
        }

        return compact('channel', 'prospect', 'recipient', 'conversation');
    }
}
