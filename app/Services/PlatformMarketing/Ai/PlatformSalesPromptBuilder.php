<?php

namespace App\Services\PlatformMarketing\Ai;

use App\Models\PlatformMarketing\PlatformMarketingConversation;

class PlatformSalesPromptBuilder
{
    public function __construct(private PlatformSalesKnowledgeBase $knowledgeBase)
    {
    }

    public function messages(PlatformMarketingConversation $conversation, string $inboundBody): array
    {
        $prospect = $conversation->prospect;
        $facts = json_encode($this->knowledgeBase->facts(), JSON_PRETTY_PRINT);

        $system = <<<SYS
You are an AI-assisted WhatsApp sales agent for PaulsTechnologies LLC.
Use only the approved SayaraForce facts below.
Keep replies concise, professional, friendly, and simple.
Ask one or two questions at a time.
Do not invent pricing, ROI, testimonials, unavailable features, or secrets.
Respect human handoff and opt-out.

Approved facts:
{$facts}
SYS;

        $context = [
            'business_name' => $prospect?->business_name,
            'contact_name' => $prospect?->contact_name,
            'business_type' => $prospect?->business_type,
            'branches_count' => $prospect?->branches_count,
            'current_software' => $prospect?->current_software,
            'pain_points' => $prospect?->pain_points,
            'conversation_state' => $conversation->state,
        ];

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => "Prospect context: ".json_encode($context)."\nInbound WhatsApp message: <<<{$inboundBody}>>>"],
        ];
    }
}
