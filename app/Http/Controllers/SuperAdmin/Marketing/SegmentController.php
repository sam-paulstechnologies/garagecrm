<?php

namespace App\Http\Controllers\SuperAdmin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\PlatformMarketing\PlatformMarketingProspect;
use App\Models\PlatformMarketing\PlatformMarketingSegment;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    public function index()
    {
        return view('super_admin.marketing.segments.index', [
            'segments' => PlatformMarketingSegment::withCount('prospects')->latest()->paginate(20),
            'prospects' => PlatformMarketingProspect::where('consent_status', 'opted_in')->orderBy('business_name')->limit(200)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'prospect_ids' => ['array'],
            'prospect_ids.*' => ['integer'],
        ]);

        $segment = PlatformMarketingSegment::query()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_dynamic' => false,
            'created_by' => $request->user()->id,
        ]);

        $segment->prospects()->sync($validated['prospect_ids'] ?? []);

        return back()->with('success', 'Segment created.');
    }

    public function show(PlatformMarketingSegment $segment)
    {
        return view('super_admin.marketing.segments.show', [
            'segment' => $segment->load('prospects'),
        ]);
    }
}
