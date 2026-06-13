<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Reports\GarageSummaryReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GarageSummaryReportController extends Controller
{
    public function index(Request $request, GarageSummaryReportService $reports): View
    {
        $companyId = (int) auth()->user()->company_id;
        $period = $this->period($request);
        $anchor = $this->anchorDate($request);

        $summary = match ($period) {
            'eow' => $reports->weeklySummary($companyId, $anchor),
            'eom' => $reports->monthlySummary($companyId, $anchor),
            default => $reports->dailySummary($companyId, $anchor),
        };

        return view('admin.reports.garage-summary', [
            'summary' => $summary,
            'period' => $period,
            'anchorDate' => $anchor,
            'periodOptions' => [
                'eod' => 'EOD',
                'eow' => 'EOW',
                'eom' => 'EOM',
            ],
        ]);
    }

    private function period(Request $request): string
    {
        $period = strtolower((string) $request->query('period', 'eod'));

        return in_array($period, ['eod', 'eow', 'eom'], true) ? $period : 'eod';
    }

    private function anchorDate(Request $request): Carbon
    {
        return $request->filled('date')
            ? Carbon::parse((string) $request->query('date'))
            : today();
    }
}
