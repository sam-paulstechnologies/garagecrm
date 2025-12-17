<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reporting\JourneySummaryService;
use Illuminate\Http\Request;

class JourneySummaryController extends Controller
{
    protected JourneySummaryService $service;

    public function __construct(JourneySummaryService $service)
    {
        $this->service = $service;
    }

    /**
     * Journeys overview: per-journey funnel + revenue + missing invoices
     */
    public function index(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $summaries = $this->service->forCompany($companyId);

        // Simple total band
        $totals = [
            'journeys'   => $summaries->count(),
            'leads'      => (int) $summaries->sum('total_leads'),
            'opps'       => (int) $summaries->sum('total_opportunities'),
            'closed_won' => (int) $summaries->sum('total_closed_won'),
            'revenue'    => (float) $summaries->sum('revenue'),
            'missing'    => (int) $summaries->sum('missing_invoice_count'),
        ];

        return view('admin.journeys.index', compact('summaries', 'totals'));
    }
}
