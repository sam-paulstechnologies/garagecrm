<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\DB;

class AiMetricsService
{
    public static function bumpAiOut(int $companyId, ?float $confidence = null, int $templateOut = 0, int $humanOut = 0, int $alerts = 0): void
    {
        $date = now()->toDateString();

        $row = DB::table('ai_metrics_daily')
            ->where('company_id', $companyId)
            ->where('report_date', $date)
            ->first();

        if (!$row) {
            DB::table('ai_metrics_daily')->insert([
                'report_date'     => $date,
                'company_id'      => $companyId,
                'ai_count'        => 0,
                'template_count'  => 0,
                'human_count'     => 0,
                'avg_confidence'  => null,
                'alerts_count'    => 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $row = DB::table('ai_metrics_daily')
                ->where('company_id', $companyId)
                ->where('report_date', $date)
                ->first();
        }

        // New totals
        $newAi       = ((int)($row->ai_count ?? 0)) + 1;
        $newTemplate = ((int)($row->template_count ?? 0)) + $templateOut;
        $newHuman    = ((int)($row->human_count ?? 0)) + $humanOut;
        $newAlerts   = ((int)($row->alerts_count ?? 0)) + $alerts;

        // Weighted avg confidence (only when provided)
        $prevAvg = $row->avg_confidence !== null ? (float)$row->avg_confidence : null;

        $newAvg = $prevAvg;
        if ($confidence !== null) {
            // Approx weighted mean: (prevAvg*(newAi-1) + confidence) / newAi
            $newAvg = $prevAvg === null
                ? (float)$confidence
                : (($prevAvg * ($newAi - 1)) + (float)$confidence) / $newAi;
        }

        DB::table('ai_metrics_daily')
            ->where('company_id', $companyId)
            ->where('report_date', $date)
            ->update([
                'ai_count'       => $newAi,
                'template_count' => $newTemplate,
                'human_count'    => $newHuman,
                'avg_confidence' => $newAvg !== null ? number_format($newAvg, 2, '.', '') : null,
                'alerts_count'   => $newAlerts,
                'updated_at'     => now(),
            ]);
    }
}
