<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ComputeDailyAiMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $forDate /* 'YYYY-MM-DD' */) {}

    public function handle(): void
    {
        $date = $this->forDate;

        $rows = DB::table('message_logs')
          ->selectRaw("
             company_id,
             SUM(source='ai')        as ai_count,
             SUM(source='template')  as template_count,
             SUM(source='human')     as human_count,
             ROUND(AVG(ai_confidence),2) as avg_confidence,
             SUM(escalation_reason IS NOT NULL) as alerts_count
          ")
          ->whereDate('created_at', $date)
          ->groupBy('company_id')
          ->get();

        foreach ($rows as $r) {
            DB::table('ai_metrics_daily')->updateOrInsert(
                ['report_date'=>$date, 'company_id'=>$r->company_id],
                [
                    'ai_count'       => (int)$r->ai_count,
                    'template_count' => (int)$r->template_count,
                    'human_count'    => (int)$r->human_count,
                    'avg_confidence' => $r->avg_confidence,
                    'alerts_count'   => (int)$r->alerts_count,
                    'updated_at'     => now(),
                ]
            );
        }
    }
}
