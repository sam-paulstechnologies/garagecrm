<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\Shared\Communication;
use Illuminate\Http\Request;

class LeadSummaryController extends Controller
{
    /**
     * GET /api/leads/{id}/summary
     * Returns a compact, denormalized snapshot for AI/automation.
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user(); // null if public; enforce as needed
        $companyId = $user?->company_id;

        $lead = Lead::query()
            ->with([
                'client:id,name,phone,email',
                'opportunity:id,lead_id,client_id,stage,created_at,assigned_to,company_id',
                'assignee:id,name',
            ])
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->findOrFail($id);

        // last communication (any channel)
        $lastComm = Communication::query()
            ->forCompany($lead->company_id)
            ->where(function ($q) use ($lead) {
                $q->where('lead_id', $lead->id)
                  ->orWhere('opportunity_id', optional($lead->opportunity)->id)
                  ->orWhere('client_id', $lead->client_id);
            })
            ->orderByDesc('communication_date')
            ->orderByDesc('id')
            ->first();

        // last 3 lifecycle-ish events we can infer with existing data
        $history = array_values(array_filter([
            $lead->created_at ? [
                'type' => 'created',
                'at'   => $lead->created_at?->toIso8601String(),
                'by'   => $lead->assignee?->name ?: null,
            ] : null,
            $lead->last_contacted_at ? [
                'type' => 'last_contact',
                'at'   => $lead->last_contacted_at?->toIso8601String(),
                'by'   => $lead->assignee?->name ?: null,
            ] : null,
            $lead->opportunity ? [
                'type' => 'converted_to_opportunity',
                'at'   => $lead->opportunity->created_at?->toIso8601String(),
                'by'   => $lead->assignee?->name ?: null,
            ] : null,
        ]));

        $summary = [
            'lead' => [
                'id'          => $lead->id,
                'name'        => $lead->name,
                'phone'       => $lead->phone,
                'email'       => $lead->email,
                'status'      => $lead->status,
                'source'      => $lead->source,
                'owner'       => $lead->assignee?->name,
                'assigned_to' => $lead->assigned_to,
                'company_id'  => $lead->company_id,
                'timestamps'  => [
                    'created_at'        => $lead->created_at?->toIso8601String(),
                    'last_contacted_at' => $lead->last_contacted_at?->toIso8601String(),
                    'qualified_at'      => null, // add if/when you store this
                ],
                'tags'        => [], // placeholder for future
            ],
            'client' => $lead->client ? [
                'id'    => $lead->client->id,
                'name'  => $lead->client->name,
                'phone' => $lead->client->phone,
                'email' => $lead->client->email,
            ] : null,
            'opportunity' => $lead->opportunity ? [
                'id'         => $lead->opportunity->id,
                'stage'      => $lead->opportunity->stage,
                'created_at' => $lead->opportunity->created_at?->toIso8601String(),
                'assigned_to'=> $lead->opportunity->assigned_to,
            ] : null,
            'last_comm' => $lastComm ? [
                'id'         => $lastComm->id,
                'channel'    => $lastComm->type, // call | email | whatsapp
                'at'         => $lastComm->communication_date?->toIso8601String(),
                'snippet'    => mb_strimwidth((string)$lastComm->content, 0, 160, '…'),
            ] : null,
            'history' => $history,
            'flags' => [
                'dedupe_matched' => false, // set true if you decide to surface from your dupes table
                'contactable'    => (bool)($lead->phone || $lead->email),
                'data_quality'   => $this->dataQuality($lead),
            ],
        ];

        return response()->json($summary);
    }

    private function dataQuality(Lead $lead): string
    {
        $score = 0;
        if ($lead->phone) $score += 1;
        if ($lead->email) $score += 1;
        if ($lead->name)  $score += 1;

        return match (true) {
            $score >= 3 => 'good',
            $score == 2 => 'ok',
            default     => 'poor',
        };
    }
}
