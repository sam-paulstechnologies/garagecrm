<?php

namespace App\Http\Controllers\SuperAdmin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\PlatformMarketing\PlatformMarketingCampaign;
use App\Models\PlatformMarketing\PlatformMarketingProspect;

class ReportController extends Controller
{
    public function index()
    {
        return view('super_admin.marketing.reports.index', [
            'sourcePerformance' => PlatformMarketingProspect::selectRaw("coalesce(source, 'Unknown') as source_name, count(*) as total")->groupBy('source_name')->orderByDesc('total')->get(),
            'campaignPerformance' => PlatformMarketingCampaign::withCount('recipients')->latest()->limit(20)->get(),
        ]);
    }
}
