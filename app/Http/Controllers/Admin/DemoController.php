<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Audience;
use App\Models\AudienceMembership;
use App\Models\JourneyEnrollment;
use App\Services\Journeys\JourneyHealthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DemoController extends Controller
{
    public function index(Request $request)
    {
        // Blade page that mounts React demo UI
        return view('admin.demo.index');
    }

    public function metrics(Request $request)
    {
        $companyId = (int) ($request->user()->company_id ?? 0);

        $conv = Conversation::query()->where('company_id', $companyId);

        $metrics = [
            'conversations_total' => (int) $conv->count(),
            'unread_total'        => (int) $conv->sum('unread_count'),
            'audiences_total'     => (int) Audience::query()
                ->where(function ($q) use ($companyId) {
                    $q->whereNull('company_id')->orWhere('company_id', $companyId);
                })
                ->where('is_active', 1)
                ->count(),
            'journeys_active'     => (int) DB::table('journeys')
                ->where('company_id', $companyId)
                ->where('is_active', 1)
                ->count(),
            'enrollments_active'  => (int) JourneyEnrollment::query()
                ->where('company_id', $companyId)
                ->where('status', 'active')
                ->count(),
        ];

        return response()->json(['ok' => true, 'metrics' => $metrics]);
    }

    public function audiencesSummary(Request $request)
    {
        $companyId = (int) ($request->user()->company_id ?? 0);

        $audiences = Audience::query()
            ->where('is_active', 1)
            ->where(function ($q) use ($companyId) {
                $q->whereNull('company_id')->orWhere('company_id', $companyId);
            })
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->limit(25)
            ->get(['id','name','is_system','company_id']);

        $ids = $audiences->pluck('id')->all();

        $counts = AudienceMembership::query()
            ->where('company_id', $companyId)
            ->whereIn('audience_id', $ids)
            ->select('audience_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('audience_id')
            ->pluck('cnt', 'audience_id')
            ->toArray();

        $items = $audiences->map(function ($a) use ($counts) {
            return [
                'id'        => (int) $a->id,
                'name'      => (string) $a->name,
                'is_system' => (bool) $a->is_system,
                'count'     => (int) ($counts[$a->id] ?? 0),
                'url'       => route('admin.audiences.show', $a->id),
            ];
        })->values();

        return response()->json(['ok' => true, 'audiences' => $items]);
    }

    public function recentEnrollments(Request $request, JourneyHealthService $health)
    {
        $companyId = (int) ($request->user()->company_id ?? 0);

        $rows = JourneyEnrollment::query()
            ->where('company_id', $companyId)
            ->with(['journey:id,name'])
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        $items = $rows->map(function ($e) use ($health) {
            $h = $health->enrollmentHealth($e);

            return [
                'id'          => (int) $e->id,
                'journey'     => (string) ($e->journey->name ?? 'Journey'),
                'status'      => (string) $e->status,
                'step'        => (int) $e->current_step_position,
                'updated_at'  => optional($e->updated_at)->toIso8601String(),
                'health'      => $h,
                'timeline_url'=> route('admin.journeys.enrollments.timeline', $e->id),
            ];
        })->values();

        return response()->json(['ok' => true, 'enrollments' => $items]);
    }
}
