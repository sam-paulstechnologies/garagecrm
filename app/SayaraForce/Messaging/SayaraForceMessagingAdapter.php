<?php

namespace App\SayaraForce\Messaging;

use App\Jobs\ProcessInboundWhatsApp;
use App\Messaging\Contracts\ProductMessagingAdapter;
use App\Messaging\Data\NormalizedIncomingMessage;

class SayaraForceMessagingAdapter implements ProductMessagingAdapter
{
    public function productKey(): string
    {
        return 'sayaraforce';
    }

    public function handleIncoming(NormalizedIncomingMessage $message): void
    {
        ProcessInboundWhatsApp::dispatch(
            from: $message->from,
            to: $message->to,
            body: $message->body,
            sid: $message->providerMessageId !== '' ? $message->providerMessageId : null,
            numMedia: $message->mediaId ? 1 : 0,
            profileName: $message->profileName,
            provider: $message->provider === 'meta_whatsapp' ? 'meta' : $message->provider,
            payload: [
                'source_kind' => $message->sourceKind,
                'message_type' => $message->type,
                'provider_timestamp' => $message->providerTimestamp,
                'media_id' => $message->mediaId,
                'media_mime_type' => $message->mediaMimeType,
                'messaging_connection_id' => $message->connectionId ?: null,
                'product_key' => $message->productKey,
            ],
            companyId: $message->companyId,
        );
    }
}
