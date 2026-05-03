<?php

namespace App\Services\Reporting;

use App\Models\Reporting\JourneySummary;
use App\Models\Client\Opportunity;
use App\Models\Job\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class JourneySummaryService
{
    /**
     * Build enriched journey summaries for a company:
     * - Pull base metrics from SQL VIEW
     * - Add conversion %, drop-off %, revenue, missing invoices
     */
    public function forCompany(int $companyId): Collection
    {
        // Base metrics from SQL view
        $base = JourneySummary::query()
            ->where('company_id', $companyId)
            ->orderBy('journey_name')
            ->get();

        if ($base->isEmpty()) {
            return collect();
        }

        $journeyIds = $base->pluck('journey_id')->all();

        // Map journey → lead_ids + client_ids (via leads)
        $leadClientMap = DB::table('journey_enrollments as je')
            ->join('leads as l', function ($join) {
                $join->on('l.id', '=', 'je.enrollable_id')
                    ->where('je.enrollable_type', '=', 'App\\Models\\Client\\Lead');
            })
            ->whereIn('je.journey_id', $journeyIds)
            ->where('je.company_id', $companyId)
            ->where('l.company_id', $companyId)
            ->select('je.journey_id', 'l.id as lead_id', 'l.client_id')
            ->get();

        // Pre-group by journey
        $byJourney = $leadClientMap
            ->groupBy('journey_id')
            ->map(function ($rows) {
                return [
                    'lead_ids'   => $rows->pluck('lead_id')->unique()->filter()->values(),
                    'client_ids' => $rows->pluck('client_id')->unique()->filter()->values(),
                ];
            });

        // Build final collection
        return $base->map(function (JourneySummary $row) use ($byJourney, $companyId) {
            $journeyId  = $row->journey_id;
            $leadIds    = $byJourney[$journeyId]['lead_ids']   ?? collect();
            $clientIds  = $byJourney[$journeyId]['client_ids'] ?? collect();

            $totalLeads         = (int) $row->total_leads;
            $totalClosedWon     = (int) $row->total_closed_won;
            $totalEnrollments   = (int) $row->total_enrollments;
            $totalOpportunities = (int) $row->total_opportunities;

            // Conversion & drop-off
            $conversionRate = $totalLeads > 0
                ? round(($totalClosedWon / $totalLeads) * 100, 1)
                : 0.0;

            $dropoffRate = $totalLeads > 0
                ? max(0.0, round((($totalLeads - $totalClosedWon) / $totalLeads) * 100, 1))
                : 0.0;

            // Revenue & missing invoices per journey (across its clients)
            $clientIdArray = $clientIds->all();

            $revenue = 0.0;
            $missingFromOpps = 0;
            $missingFromJobs = 0;

            if (!empty($clientIdArray)) {
                // 1) Opportunities closed_won for these clients
                $closedWonOpps = Opportunity::query()
                    ->where('company_id', $companyId)
                    ->where('stage', Opportunity::STAGE_CLOSED_WON)
                    ->whereIn('client_id', $clientIdArray)
                    ->get(['id', 'client_id']);

                $closedWonClientIds = $closedWonOpps->pluck('client_id')->unique()->values();

                // 2) All invoices for these clients (we use PAID only as revenue)
                $invoices = Invoice::query()
                    ->where('company_id', $companyId)
                    ->whereIn('client_id', $clientIdArray)
                    ->get(['client_id', 'amount', 'status']);

                $revenue = (float) $invoices
                    ->where('status', 'paid')
                    ->sum(fn ($inv) => (float) ($inv->amount ?? 0));

                $clientsWithAnyInvoice = $invoices
                    ->pluck('client_id')
                    ->unique()
                    ->values();

                // Missing invoices from closed_won = closed_won clients without ANY invoice
                $missingFromOpps = $closedWonClientIds
                    ->reject(fn ($cid) => $clientsWithAnyInvoice->contains($cid))
                    ->count();

                // 3) Completed jobs (end_time != null) without invoice for these clients
                $missingFromJobs = DB::table('jobs as j')
                    ->where('j.company_id', $companyId)
                    ->whereIn('j.client_id', $clientIdArray)
                    ->whereNotNull('j.end_time')
                    ->whereNotExists(function ($q) {
                        $q->select(DB::raw(1))
                            ->from('invoices as i')
                            ->whereColumn('i.job_id', 'j.id')
                            ->whereNull('i.deleted_at');
                    })
                    ->count();
            }

            $missingInvoiceCount = $missingFromOpps + $missingFromJobs;

            return [
                'journey_id'            => $journeyId,
                'company_id'            => $row->company_id,
                'journey_name'          => $row->journey_name,
                'total_enrollments'     => $totalEnrollments,
                'total_leads'           => $totalLeads,
                'total_opportunities'   => $totalOpportunities,
                'total_closed_won'      => $totalClosedWon,
                'conversion_rate'       => $conversionRate,
                'dropoff_rate'          => $dropoffRate,
                'revenue'               => $revenue,
                'missing_invoice_count' => $missingInvoiceCount,
            ];
        });
    }
}