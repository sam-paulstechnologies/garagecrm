<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JourneyEnrollment;
use App\Models\JourneyAction;
use App\Services\Journeys\JourneyActionService;
use App\Services\Journeys\JourneyHealthService;
use Illuminate\Http\Request;

class JourneyEnrollmentAdminController extends Controller
{
    public function index(Request $request, JourneyHealthService $health)
    {
        $companyId = function_exists('company_id')
            ? (int) company_id()
            : (int) (auth()->user()->company_id ?? 0);

        $query = JourneyEnrollment::query()
            ->where('company_id', $companyId)
            ->with(['journey'])
            ->orderByDesc('updated_at');

        if ($jid = $request->get('journey_id')) {
            $query->where('journey_id', (int) $jid);
        }

        $enrollments = $query->paginate(50);

        // attach computed health
        $enrollments->getCollection()->transform(function ($e) use ($health) {
            $e->_health = $health->enrollmentHealth($e);
            return $e;
        });

        return view('admin.journeys.phase9.enrollments.index', [
            'enrollments' => $enrollments,
        ]);
    }

    public function show(Request $request, JourneyEnrollment $enrollment, JourneyHealthService $health)
    {
        $this->authorize('view', $enrollment);

        $enrollment->load(['journey.steps']);

        $actions = JourneyAction::query()
            ->where('company_id', (int) $enrollment->company_id)
            ->where('enrollment_id', (int) $enrollment->id)
            ->orderByDesc('created_at')
            ->limit(200)
            ->get();

        return view('admin.journeys.phase9.enrollments.show', [
            'enrollment' => $enrollment,
            'journey' => $enrollment->journey,
            'health' => $health->enrollmentHealth($enrollment),
            'actions' => $actions,
        ]);
    }

    public function pause(Request $request, JourneyEnrollment $enrollment, JourneyActionService $svc)
    {
        $this->authorize('act', $enrollment);

        $svc->pause($enrollment, (int) auth()->id(), (string) $request->input('reason', ''));

        return back()->with('success', 'Enrollment paused.');
    }

    public function resume(Request $request, JourneyEnrollment $enrollment, JourneyActionService $svc)
    {
        $this->authorize('act', $enrollment);

        $svc->resume($enrollment, (int) auth()->id(), (string) $request->input('reason', ''));

        return back()->with('success', 'Enrollment resumed.');
    }

    public function skip(Request $request, JourneyEnrollment $enrollment, JourneyActionService $svc)
    {
        $this->authorize('act', $enrollment);

        $enrollment->load(['journey.steps']);
        $svc->skipStep($enrollment, (int) auth()->id(), (string) $request->input('reason', ''));

        return back()->with('success', 'Skipped one step.');
    }

    public function forceAdvance(Request $request, JourneyEnrollment $enrollment, JourneyActionService $svc)
    {
        $this->authorize('act', $enrollment);

        $enrollment->load(['journey.steps']);
        $pos = (int) $request->input('position', (int)$enrollment->current_step_position);

        $svc->forceAdvanceTo($enrollment, (int) auth()->id(), $pos, (string) $request->input('reason', ''));

        return back()->with('success', 'Advanced to requested step position.');
    }
}
