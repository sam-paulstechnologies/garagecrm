<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SlaDashboardController extends Controller
{
    public function index(Request $request)
    {
        $companyId = (int) $request->user()->company_id;

        $now = Carbon::now();

        // ------------------------------
        // 1. BASIC COUNTS
        // ------------------------------
        $todayCount = Conversation::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->count();

        $openCount = Conversation::where('company_id', $companyId)
            ->where('status', 'open')
            ->count();

        // ------------------------------
        // 2. AVG FIRST RESPONSE TIME
        // ------------------------------
        $firstResponse = DB::table('message_logs as m1')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, m1.created_at, m2.created_at)) as avg_seconds')
            ->join('message_logs as m2', function ($j) {
                $j->on('m1.conversation_id', '=', 'm2.conversation_id')
                  ->where('m2.direction', 'out');
            })
            ->where('m1.direction', 'in')
            ->where('m1.company_id', $companyId)
            ->where('m2.company_id', $companyId)
            ->groupBy('m1.conversation_id')
            ->value('avg_seconds');

        $avgFirstResponseMinutes = $firstResponse ? round($firstResponse / 60, 1) : 0;

        // ------------------------------
        // 3. SLA BREACHES (No reply for 15 mins)
        // ------------------------------
        $slaBreaches = Conversation::where('company_id', $companyId)
            ->where('status', 'open')
            ->where('last_message_at', '<', now()->subMinutes(15))
            ->count();

        // ------------------------------
        // 4. AI vs Human breakdown
        // ------------------------------
        $aiCount = MessageLog::where('company_id', $companyId)
            ->where('source', 'ai')
            ->count();

        $humanCount = MessageLog::where('company_id', $companyId)
            ->where('source', 'human')
            ->count();

        // ------------------------------
        // 5. Agent Performance
        // ------------------------------
        $agentPerformance = MessageLog::query()
            ->select('users.name',
                DB::raw('COUNT(message_logs.id) as total'),
                DB::raw('SUM(message_logs.source="ai") as ai_count'),
                DB::raw('SUM(message_logs.direction="out") as outbound')
            )
            ->join('users', 'users.id', '=', 'message_logs.user_id')
            ->where('message_logs.company_id', $companyId)
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // ------------------------------
        // 6. Ageing Buckets
        // ------------------------------
        $ageBuckets = [
            '0_15'   => $this->countBucket($companyId, 0, 15),
            '15_60'  => $this->countBucket($companyId, 15, 60),
            '1_3h'   => $this->countBucket($companyId, 60, 180),
            '3_24h'  => $this->countBucket($companyId, 180, 1440),
            '1_3d'   => $this->countBucket($companyId, 1440, 4320),
            '3d_plus'=> $this->countBucket($companyId, 4320, null),
        ];

        return view('admin.sla.dashboard', [
            'todayCount'              => $todayCount,
            'openCount'               => $openCount,
            'avgFirstResponseMinutes' => $avgFirstResponseMinutes,
            'slaBreaches'             => $slaBreaches,
            'aiCount'                 => $aiCount,
            'humanCount'              => $humanCount,
            'agentPerformance'        => $agentPerformance,
            'ageBuckets'              => $ageBuckets,
        ]);
    }

    private function countBucket($companyId, $minMinutes, $maxMinutes = null)
    {
        $query = Conversation::where('company_id', $companyId)
            ->where('status', 'open')
            ->whereNotNull('last_message_at');

        $cutoff = now()->subMinutes($minMinutes);
        $query->where('last_message_at', '<=', $cutoff);

        if ($maxMinutes !== null) {
            $upper = now()->subMinutes($maxMinutes);
            $query->where('last_message_at', '>=', $upper);
        }

        return $query->count();
    }
}
