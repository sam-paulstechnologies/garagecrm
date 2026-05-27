<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client\Lead;
use App\Models\Shared\Communication;
use Illuminate\Http\Request;

class LeadSummaryController extends Controller
{
    /**
     * GET /api/v1/leads/{id}/summary
     *
     * Returns a compact, denormalized lead snapshot for AI/automation.
     *
     * Security:
     * - Authenticated route only.
     * - Requires authenticated user to have company_id.
     * - Lead must belong to the authenticated user's company.
     * - Related client/opportunity data is also company-scoped.
     */
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        abort_if(! $user, 401);

        $companyId = (int) ($user->company_id ?? 0);

        abort_if(! $companyId, 403);

        $lead = Lead::query()
            ->where('company_id', $companyId)
            ->where('id', $id)
            ->with([
                'client' => function ($q) use ($companyId) {
                    $q->select('id', 'company_id', 'name', 'phone', 'email')
                        ->where('company_id', $companyId);
                },
                'opportunity' => function ($q) use ($companyId) {
                    $q->select('id', 'company_id', 'lead_id', 'client_id', 'stage', 'created_at', 'assigned_to')
                        ->where('company_id', $companyId);
                },
                'assignee:id,name,company_id',
            ])
            ->firstOrFail();

        $lastComm = Communication::query()
            ->forCompany($companyId)
            ->where(function ($q) use ($lead) {
                $q->where('lead_id', $lead->id);

                if ($lead->opportunity?->id) {
                    $q->orWhere('opportunity_id', $lead->opportunity->id);
                }

                if ($lead->client_id) {
                    $q->orWhere('client_id', $lead->client_id);
                }
            })
            ->orderByDesc('communication_date')
            ->orderByDesc('id')
            ->first();

        $history = array_values(array_filter([
            $lead->created_at ? [
                'type' => 'created',
                'at' => $lead->created_at?->toIso8601String(),
                'by' => $lead->assignee?->name ?: null,
            ] : null,

            $lead->last_contacted_at ? [
                'type' => 'last_contact',
                'at' => $lead->last_contacted_at?->toIso8601String(),
                'by' => $lead->assignee?->name ?: null,
            ] : null,

            $lead->opportunity ? [
                'type' => 'converted_to_opportunity',
                'at' => $lead->opportunity->created_at?->toIso8601String(),
                'by' => $lead->assignee?->name ?: null,
            ] : null,
        ]));

        $summary = [
            'lead' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'status' => $lead->status,
                'source' => $lead->source,
                'owner' => $lead->assignee?->name,
                'assigned_to' => $lead->assigned_to,
                'company_id' => $lead->company_id,
                'timestamps' => [
                    'created_at' => $lead->created_at?->toIso8601String(),
                    'last_contacted_at' => $lead->last_contacted_at?->toIso8601String(),
                    'qualified_at' => null,
                ],
                'tags' => [],
            ],

            'client' => $lead->client ? [
                'id' => $lead->client->id,
                'name' => $lead->client->name,
                'phone' => $lead->client->phone,
                'email' => $lead->client->email,
            ] : null,

            'opportunity' => $lead->opportunity ? [
                'id' => $lead->opportunity->id,
                'stage' => $lead->opportunity->stage,
                'created_at' => $lead->opportunity->created_at?->toIso8601String(),
                'assigned_to' => $lead->opportunity->assigned_to,
            ] : null,

            'last_comm' => $lastComm ? [
                'id' => $lastComm->id,
                'channel' => $lastComm->type,
                'at' => $lastComm->communication_date?->toIso8601String(),
                'snippet' => mb_strimwidth((string) $lastComm->content, 0, 160, '…'),
            ] : null,

            'history' => $history,

            'flags' => [
                'dedupe_matched' => false,
                'contactable' => (bool) ($lead->phone || $lead->email),
                'data_quality' => $this->dataQuality($lead),
            ],
        ];

        return response()->json($summary);
    }

    private function dataQuality(Lead $lead): string
    {
        $score = 0;

        if ($lead->phone) {
            $score++;
        }

        if ($lead->email) {
            $score++;
        }

        if ($lead->name) {
            $score++;
        }

        return match (true) {
            $score >= 3 => 'good',
            $score === 2 => 'ok',
            default => 'poor',
        };
    }
}