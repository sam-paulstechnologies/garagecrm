<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\JobCard;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class JobCardController extends Controller
{
    public function index()
    {
        $jobCards = JobCard::with('job')
            ->where('company_id', Auth::user()->company_id)
            ->get();

        return view('jobcards.index', compact('jobCards'));
    }

    public function create()
    {
        $bookings = Booking::where('company_id', Auth::user()->company_id)->get();
        return view('jobcards.create', compact('bookings'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'job_id'          => 'required|exists:jobs,id',
            'before_photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'after_photos.*'  => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'notes'           => 'nullable|string',
            'mechanic'        => 'required|string|max:255',
        ]);

        $beforePhotos = $this->uploadPhotos($request->file('before_photos'));
        $afterPhotos  = $this->uploadPhotos($request->file('after_photos'));

        JobCard::create([
            'job_id'        => $request->job_id,
            'before_photos' => json_encode($beforePhotos),
            'after_photos'  => json_encode($afterPhotos),
            'notes'         => $request->notes,
            'mechanic'      => $request->mechanic,
            'company_id'    => Auth::user()->company_id,
        ]);

        return redirect()->route('jobcards.index')->with('success', 'Job card created successfully.');
    }

    public function show(JobCard $jobCard)
    {
        $this->authorizeCompany($jobCard);
        return view('jobcards.show', compact('jobCard'));
    }

    public function edit(JobCard $jobCard)
    {
        $this->authorizeCompany($jobCard);
        $bookings = Booking::where('company_id', Auth::user()->company_id)->get();
        return view('jobcards.edit', compact('jobCard', 'bookings'));
    }

    public function update(Request $request, JobCard $jobCard)
    {
        $this->authorizeCompany($jobCard);

        $request->validate([
            'job_id'          => 'required|exists:jobs,id',
            'before_photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'after_photos.*'  => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'notes'           => 'nullable|string',
            'mechanic'        => 'required|string|max:255',
        ]);

        $beforePhotos = $this->uploadPhotos($request->file('before_photos'));
        $afterPhotos  = $this->uploadPhotos($request->file('after_photos'));

        $jobCard->update([
            'job_id'        => $request->job_id,
            'before_photos' => json_encode($beforePhotos),
            'after_photos'  => json_encode($afterPhotos),
            'notes'         => $request->notes,
            'mechanic'      => $request->mechanic,
        ]);

        return redirect()->route('jobcards.index')->with('success', 'Job card updated successfully.');
    }

    public function destroy(JobCard $jobCard)
    {
        $this->authorizeCompany($jobCard);
        $jobCard->delete();

        return redirect()->route('jobcards.index')->with('success', 'Job card deleted successfully.');
    }

    private function authorizeCompany(JobCard $jobCard)
    {
        if ($jobCard->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized action.');
        }
    }

    private function uploadPhotos($photos)
    {
        $paths = [];
        if ($photos) {
            foreach ($photos as $photo) {
                $paths[] = $photo->store('job_photos', 'public');
            }
        }
        return $paths;
    }
}
