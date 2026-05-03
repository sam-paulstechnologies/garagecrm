<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JourneyEnrollment;
use App\Services\Journeys\JourneyTimelineBuilder;
use Illuminate\Http\Request;

class JourneyTimelineController extends Controller
{
    public function show(Request $request, JourneyEnrollment $enrollment, JourneyTimelineBuilder $builder)
    {
        $companyId = function_exists('company_id')
            ? (int) company_id()
            : (int) (auth()->user()->company_id ?? 0);

        // Safety: must belong to same company
        if ((int) $enrollment->company_id !== $companyId) {
            abort(403);
        }

        $enrollment->load(['journey.steps']);

        $timeline = $builder->build($enrollment);

        return view('admin.journeys.timeline', [
            'enrollment' => $enrollment,
            'journey'    => $enrollment->journey,
            'timeline'   => $timeline,
        ]);
    }
}
