<?php

namespace App\Services\Metrics;

use Illuminate\Support\Facades\DB;

class InsightsService
{
    public function daily(int $companyId, ?string $from = null, ?string $to = null)
    {
        $q = DB::table('vw_ai_metrics_daily')
            ->where('company_id', $companyId);

        if ($from) $q->where('report_date','>=',$from);
        if ($to)   $q->where('report_date','<=',$to);

        return $q->orderBy('report_date')->get();
    }

    public function breakdownToday(int $companyId)
    {
        return DB::table('message_logs')
            ->selectRaw("
               SUM(source='ai')        as ai_count,
               SUM(source='template')  as template_count,
               SUM(source='human')     as human_count,
               ROUND(AVG(ai_confidence),2) as avg_confidence,
               SUM(escalation_reason IS NOT NULL) as alerts_count
            ")
            ->where('company_id',$companyId)
            ->whereDate('created_at', now()->toDateString())
            ->first();
    }
}
