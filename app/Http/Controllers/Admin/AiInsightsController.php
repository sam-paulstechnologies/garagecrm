<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiInsightsController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) ($request->user()->company_id ?? 0);
        $days      = (int) ($request->query('days', 30)); // switch 7/30 via query

        // Window
        $since = now()->subDays(max(1, $days));

        // Outbound breakdown (template vs non-template)
        $out = MessageLog::query()
            ->where('company_id', $companyId)
            ->where('direction', 'out')
            ->where('created_at', '>=', $since)
            ->selectRaw("
                SUM(CASE WHEN template IS NOT NULL AND template <> '' THEN 1 ELSE 0 END) as template_out,
                SUM(CASE WHEN (template IS NULL OR template = '') AND (source = 'ai') THEN 1 ELSE 0 END) as ai_text_out,
                SUM(CASE WHEN (template IS NULL OR template = '') AND (source IS NULL OR source <> 'ai') THEN 1 ELSE 0 END) as human_text_out,
                COUNT(*) as total_out
            ")
            ->first();

        // Inbound average confidence (from ai_analysis->confidence)
        $avgConfidence = (float) (MessageLog::query()
            ->where('company_id', $companyId)
            ->where('direction', 'in')
            ->where('created_at', '>=', $since)
            ->whereNotNull('ai_analysis')
            ->select(DB::raw("AVG( (JSON_EXTRACT(ai_analysis, '$.confidence')) ) as avg_conf"))
            ->value('avg_conf') ?? 0.0);

        // Manager alerts summary (by template name we send on handoff)
        $alerts = MessageLog::query()
            ->where('company_id', $companyId)
            ->where('direction', 'out')
            ->where('created_at', '>=', $since)
            ->where('template', 'manager_call_lead')
            ->select('created_at','to_number','lead_id','provider_status')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        // AI vs Template %
        $templateOut   = (int) ($out->template_out ?? 0);
        $aiTextOut     = (int) ($out->ai_text_out ?? 0);
        $humanTextOut  = (int) ($out->human_text_out ?? 0);
        $totalOut      = max(1, (int) ($out->total_out ?? 0));

        $pctTemplate = round(($templateOut  / $totalOut) * 100);
        $pctAIText   = round(($aiTextOut    / $totalOut) * 100);
        $pctHuman    = round(($humanTextOut / $totalOut) * 100);

        return view('admin.ai.insights', [
            'days'         => $days,
            'since'        => $since,
            'pctTemplate'  => $pctTemplate,
            'pctAIText'    => $pctAIText,
            'pctHuman'     => $pctHuman,
            'avgConfidence'=> round($avgConfidence, 3),
            'templateOut'  => $templateOut,
            'aiTextOut'    => $aiTextOut,
            'humanTextOut' => $humanTextOut,
            'totalOut'     => $totalOut,
            'alerts'       => $alerts,
        ]);
    }
}
