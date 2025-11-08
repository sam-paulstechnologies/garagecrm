<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Metrics\InsightsService;
use Illuminate\Http\Request;

class InsightsApiController extends Controller
{
    public function __construct(protected InsightsService $svc) {}

    public function daily(Request $request)
    {
        $companyId = (int) optional($request->user())->company_id ?: 1;
        $from = $request->query('from');
        $to   = $request->query('to');
        return response()->json($this->svc->daily($companyId,$from,$to));
    }

    public function today(Request $request)
    {
        $companyId = (int) optional($request->user())->company_id ?: 1;
        return response()->json($this->svc->breakdownToday($companyId));
    }
}
