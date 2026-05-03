<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journey;
use App\Models\JourneyEnrollment;
use App\Services\Journeys\JourneyHealthService;
use Illuminate\Http\Request;

class JourneyDashboardController extends Controller
{
    public function index(Request $request, JourneyHealthService $health)
    {
        $companyId = function_exists('company_id')
            ? (int) company_id()
            : (int) (auth()->user()->company_id ?? 0);

        $journeys = Journey::query()
            ->where('company_id', $companyId)
            ->withCount(['steps'])
            ->orderBy('name')
            ->get();

        // Enrollment aggregates
        $enrollments = JourneyEnrollment::query()
            ->where('company_id', $companyId)
            ->with(['journey.steps'])
            ->latest('updated_at')
            ->limit(2000)
            ->get();

        $byJourney = [];
        foreach ($journeys as $j) {
            $byJourney[$j->id] = [
                'journey' => $j,
                'active' => 0,
                'paused' => 0,
                'completed' => 0,
                'waiting' => 0,
                'stuck' => 0,
                'last_activity' => null,
                'avg_minutes_since_update' => null,
            ];
        }

        foreach ($enrollments as $e) {
            if (!isset($byJourney[$e->journey_id])) continue;

            $h = $health->enrollmentHealth($e);
            $status = strtolower((string) ($e->status ?? 'active'));

            if (in_array($status, ['completed', 'done'], true)) $byJourney[$e->journey_id]['completed']++;
            elseif ($status === 'paused') $byJourney[$e->journey_id]['paused']++;
            else $byJourney[$e->journey_id]['active']++;

            if ($h['badge'] === 'waiting') $byJourney[$e->journey_id]['waiting']++;
            if ($h['badge'] === 'stuck') $byJourney[$e->journey_id]['stuck']++;

            $la = $byJourney[$e->journey_id]['last_activity'];
            if (!$la || ($e->updated_at && $e->updated_at > $la)) {
                $byJourney[$e->journey_id]['last_activity'] = $e->updated_at;
            }

            // crude avg (good enough for 9E-lite)
            $byJourney[$e->journey_id]['_mins_sum'] = ($byJourney[$e->journey_id]['_mins_sum'] ?? 0) + (int)$h['minutes_since_update'];
            $byJourney[$e->journey_id]['_mins_cnt'] = ($byJourney[$e->journey_id]['_mins_cnt'] ?? 0) + 1;
        }

        foreach ($byJourney as $jid => $row) {
            $cnt = (int)($row['_mins_cnt'] ?? 0);
            $byJourney[$jid]['avg_minutes_since_update'] = $cnt ? round(($row['_mins_sum'] ?? 0) / $cnt) : null;
            unset($byJourney[$jid]['_mins_sum'], $byJourney[$jid]['_mins_cnt']);
        }

        return view('admin.journeys.phase9.dashboard', [
            'rows' => array_values($byJourney),
        ]);
    }
}
