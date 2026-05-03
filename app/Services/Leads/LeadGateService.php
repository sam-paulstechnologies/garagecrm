<?php

namespace App\Services\Leads;

use App\Models\Client\Lead;
use App\Models\Client\Client;
use App\Models\Client\Opportunity;
use App\Services\WhatsApp\WhatsAppService;
use App\Services\Moderation\ProfanityGuard;
use App\Models\TimelineComment;
use Illuminate\Support\Facades\DB;

class LeadGateService
{
    public function __construct(
        protected WhatsAppService $whatsapp,
        protected ProfanityGuard $profanity
    ) {}

    /**
     * Main entry point after lead creation
     */
    public function process(Lead $lead): void
    {
        // 1️⃣ Send WhatsApp
        $res = $this->whatsapp->sendTemplate(
            $lead->phone,
            'lead_acknowledgment_v2',
            [],
            [],
            ['company_id' => $lead->company_id]
        );

        // 2️⃣ Failed WhatsApp → manager review
        if (isset($res['error'])) {
            $this->handover($lead, 'WhatsApp not delivered');
            return;
        }

        // 3️⃣ Passed WhatsApp → auto qualify
        $this->autoConvert($lead);
    }

    protected function autoConvert(Lead $lead): void
    {
        DB::transaction(function () use ($lead) {

            $client = Client::firstOrCreate(
                ['company_id' => $lead->company_id, 'phone' => $lead->phone],
                ['name' => $lead->name, 'email' => $lead->email]
            );

            Opportunity::firstOrCreate(
                ['lead_id' => $lead->id, 'company_id' => $lead->company_id],
                [
                    'client_id' => $client->id,
                    'stage'     => 'new',
                    'source'    => $lead->source,
                ]
            );

            $lead->update(['status' => 'auto_qualified']);

            TimelineComment::system(
                $lead->company_id,
                'lead',
                $lead->id,
                'WhatsApp delivered. Lead auto-qualified.'
            );
        });
    }

    protected function handover(Lead $lead, string $reason): void
    {
        $lead->update(['status' => 'manager_review']);

        TimelineComment::system(
            $lead->company_id,
            'lead',
            $lead->id,
            "Lead handed to manager: {$reason}"
        );
    }
}
