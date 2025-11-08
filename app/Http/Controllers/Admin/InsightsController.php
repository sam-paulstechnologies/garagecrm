<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Metrics\InsightsService;
use Illuminate\Http\Request;

class InsightsController extends Controller
{
    public function __construct(protected InsightsService $svc) {}

    public function monitoring(Request $request)
    {
        $companyId = (int) optional($request->user())->company_id ?: 1;
        $from = $request->query('from');
        $to   = $request->query('to');

        $daily = $this->svc->daily($companyId, $from, $to);
        $today = $this->svc->breakdownToday($companyId);

        return view('admin.insights.monitoring', compact('daily','today'));
    }
}
