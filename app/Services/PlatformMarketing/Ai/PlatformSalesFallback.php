<?php

namespace App\Services\PlatformMarketing\Ai;

use App\Models\PlatformMarketing\PlatformMarketingConversation;

class PlatformSalesFallback
{
    public function reply(PlatformMarketingConversation $conversation, string $inboundBody): array
    {
        $body = strtolower(trim($inboundBody));

        if (str_contains($body, 'demo') || str_contains($body, 'meeting') || str_contains($body, 'book')) {
            return [
                'body' => "Happy to help with a SayaraForce demo. What day and time works best for you, and which email should we use for the invite?",
                'state' => 'collect_demo_details',
                'qualification_status' => 'demo_requested',
            ];
        }

        if (str_contains($body, 'price') || str_contains($body, 'cost')) {
            return [
                'body' => "Pricing depends on the setup and current offer, so I should not guess. I can arrange a quick SayaraForce demo and the team can explain the best-fit plan. How many branches do you manage?",
                'state' => 'qualify_size',
                'qualification_status' => 'engaged',
            ];
        }

        if (in_array($body, ['hi', 'hello', 'hey', 'start'], true)) {
            return [
                'body' => "Hi, this is the SayaraForce sales assistant from PaulsTechnologies. We help garages manage leads, WhatsApp follow-up, bookings, jobs, and customers in one CRM. Are you running a garage or automotive service business?",
                'state' => 'identify_business',
                'qualification_status' => 'engaged',
            ];
        }

        return [
            'body' => "Thanks for sharing that. To see if SayaraForce fits, can you tell me your business type and how you currently handle WhatsApp leads or bookings?",
            'state' => 'understand_current_process',
            'qualification_status' => 'engaged',
        ];
    }
}
