<?php

namespace App\Http\Controllers\SuperAdmin\Marketing;

use App\Http\Controllers\Controller;
use App\Models\PlatformMarketing\PlatformMarketingAppointment;
use App\Models\PlatformMarketing\PlatformMarketingProspect;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AppointmentController extends Controller
{
    public function index()
    {
        return view('super_admin.marketing.appointments.index', [
            'appointments' => PlatformMarketingAppointment::latest('starts_at')->paginate(20),
            'prospects' => PlatformMarketingProspect::orderBy('business_name')->limit(200)->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prospect_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:240'],
            'timezone' => ['required', 'string', 'max:80'],
            'meeting_mode' => ['required', 'string', 'max:40'],
            'meeting_link' => ['nullable', 'string', 'max:255'],
            'internal_notes' => ['nullable', 'string'],
        ]);

        $startsAt = Carbon::parse($validated['starts_at']);

        PlatformMarketingAppointment::query()->create([
            'prospect_id' => $validated['prospect_id'],
            'status' => 'confirmed',
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addMinutes((int) $validated['duration_minutes']),
            'timezone' => $validated['timezone'],
            'meeting_mode' => $validated['meeting_mode'],
            'meeting_link' => $validated['meeting_link'] ?? null,
            'internal_notes' => $validated['internal_notes'] ?? null,
        ]);

        PlatformMarketingProspect::query()->whereKey($validated['prospect_id'])->update([
            'status' => 'demo_booked',
            'demo_booked_at' => now(),
        ]);

        return back()->with('success', 'Demo appointment booked.');
    }
}
