<?php

namespace App\QuickScan;

use App\Models\QuickScan\QuickScanCandidate;
use App\Models\QuickScan\QuickScanWorkspace;
use App\Services\Ai\NlpService;
use App\Services\WhatsApp\History\HistorySignalAnalyzer;
use Illuminate\Support\Facades\DB;

final class QuickScanAnalysis
{
    public function __construct(
        private readonly HistorySignalAnalyzer $signals,
        private readonly NlpService $nlp,
        private readonly QuickScanReport $report,
        private readonly QuickScanAudit $audit,
    ) {}

    public function run(QuickScanWorkspace $scan, bool $allowExternalAi = true): QuickScanWorkspace
    {
        $started = hrtime(true);
        $scan->forceFill(['status' => 'analysing', 'analysis_started_at' => $scan->analysis_started_at ?? now()])->save();
        $limit = max(1, (int) $scan->analysis_limit);

        $scan->candidates()->where('deterministic_excluded', true)->update([
            'intelligence_status' => 'deterministic',
            'classification' => 'possible_colleague',
            'classification_reason' => null,
            'retention_level' => 'none',
            'retention_reason' => null,
            'updated_at' => now(),
        ]);

        $alreadyCounted = $scan->candidates()->whereNotNull('analysis_counted_at')->count();
        $available = max(0, $limit - $alreadyCounted);
        $candidates = $scan->candidates()
            ->where('deterministic_excluded', false)
            ->whereNull('analysis_counted_at')
            ->orderByDesc('message_count')
            ->orderBy('id')
            ->get();

        foreach ($candidates as $candidate) {
            if ($available < 1) {
                $candidate->forceFill(['intelligence_status' => 'locked'])->save();

                continue;
            }
            $this->analyseCandidate($candidate, $allowExternalAi);
            $available--;
        }

        $duration = (int) round((hrtime(true) - $started) / 1_000_000);
        $scan->forceFill([
            'contacts_discovered' => $scan->candidates()->count(),
            'contacts_deterministic_excluded' => $scan->candidates()->where('deterministic_excluded', true)->count(),
            'contacts_analysed' => $scan->candidates()->whereNotNull('analysis_counted_at')->count(),
            'ai_calls' => $scan->candidates()->where('intelligence_status', 'analysed_ai')->count(),
            'analysis_duration_ms' => $duration,
            'analysis_completed_at' => now(),
        ])->save();
        $this->audit->record($scan, 'quick_scan.analysis_completed', context: [
            'contacts_discovered' => $scan->contacts_discovered,
            'contacts_analysed' => $scan->contacts_analysed,
            'deterministic_excluded' => $scan->contacts_deterministic_excluded,
            'ai_calls' => $scan->ai_calls,
            'analysis_limit' => $limit,
            'duration_ms' => $duration,
        ]);

        return $this->report->prepare($scan->fresh());
    }

    private function analyseCandidate(QuickScanCandidate $candidate, bool $allowExternalAi): void
    {
        DB::transaction(function () use ($candidate, $allowExternalAi): void {
            $candidate = QuickScanCandidate::query()->with('messages')->lockForUpdate()->findOrFail($candidate->id);
            if ($candidate->analysis_counted_at) {
                return;
            }
            $conversation = $this->signals->conversation($candidate->messages);
            $age = $candidate->last_message_at?->diffInDays(now()) ?? 0;
            $aiConfigured = $allowExternalAi && filled(config('services.openai.api_key'));
            $semantic = $allowExternalAi
                ? $this->nlp->analyzeHistoryConversation($conversation['text'], $age)
                : null;
            $result = $this->signals->evaluate($conversation['text'], $age, $semantic);
            $candidate->forceFill([
                'analysis_fingerprint' => $conversation['fingerprint'],
                'intelligence_status' => $aiConfigured ? 'analysed_ai' : 'analysed_rules',
                'classification' => $result['classification'],
                'classification_confidence' => $result['classification_confidence'],
                'classification_reason' => $result['classification_reason'],
                'retention_level' => $result['retention_level'],
                'retention_confidence' => $result['retention_confidence'],
                'retention_reason' => $result['retention_reason'],
                'potential_missed' => $result['potential_missed'],
                'quote_unresolved' => $result['quote_unresolved'],
                'service_related' => $result['service_related'],
                'analysis_counted_at' => now(),
                'analysed_at' => now(),
            ])->save();
        }, 3);
    }
}
