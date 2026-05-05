<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Metrics\InsightsService;
use Illuminate\Http\Request;

class InsightsApiController extends Controller
{
    public function __construct(protected InsightsService $svc) {}

    protected function companyId(Request $request): int
    {
        $companyId = (int) ($request->user()?->company_id ?? 0);

        abort_if(!$companyId, 403);

        return $companyId;
    }

    public function daily(Request $request)
    {
        $companyId = $this->companyId($request);
        $from = $request->query('from');
        $to   = $request->query('to');

        return response()->json($this->svc->daily($companyId, $from, $to));
    }

    public function today(Request $request)
    {
        $companyId = $this->companyId($request);

        return response()->json($this->svc->breakdownToday($companyId));
    }
}