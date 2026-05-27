<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Job\Booking;
use App\Models\Job\Job;
use App\Models\Job\JobCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class JobCardController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function companyId(): int
    {
        $companyId = (int) (Auth::user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    protected function authorizeCompany(JobCard $jobCard): void
    {
        abort_unless((int) $jobCard->company_id === $this->companyId(), 404);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $companyId = $this->companyId();

        $jobCards = JobCard::query()
            ->with([
                'job' => fn ($query) => $query->where('company_id', $companyId),
            ])
            ->where('company_id', $companyId)
            ->latest('id')
            ->paginate(20);

        return view('jobcards.index', compact('jobCards'));
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $companyId = $this->companyId();

        $bookings = Booking::query()
            ->where('company_id', $companyId)
            ->latest('id')
            ->get();

        $jobs = Job::query()
            ->where('company_id', $companyId)
            ->latest('id')
            ->get(['id', 'job_code', 'client_id', 'status']);

        return view('jobcards.create', compact('bookings', 'jobs'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $companyId = $this->companyId();

        $data = $request->validate([
            'job_id' => [
                'required',
                Rule::exists('jobs', 'id')->where('company_id', $companyId),
            ],
            'before_photos' => ['nullable', 'array'],
            'before_photos.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'after_photos' => ['nullable', 'array'],
            'after_photos.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'mechanic' => ['required', 'string', 'max:255'],
        ]);

        $job = Job::query()
            ->where('company_id', $companyId)
            ->findOrFail($data['job_id']);

        $beforePhotos = $this->uploadPhotos($request->file('before_photos'), $companyId, $job->id);
        $afterPhotos = $this->uploadPhotos($request->file('after_photos'), $companyId, $job->id);

        JobCard::create([
            'job_id' => $job->id,
            'before_photos' => json_encode($beforePhotos),
            'after_photos' => json_encode($afterPhotos),
            'notes' => $data['notes'] ?? null,
            'mechanic' => $data['mechanic'],
            'company_id' => $companyId,
        ]);

        return redirect()
            ->route('tenant.jobcards.index')
            ->with('success', 'Job card created successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(JobCard $jobCard)
    {
        $this->authorizeCompany($jobCard);

        $companyId = $this->companyId();

        $jobCard->load([
            'job' => fn ($query) => $query->where('company_id', $companyId),
        ]);

        return view('jobcards.show', compact('jobCard'));
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(JobCard $jobCard)
    {
        $this->authorizeCompany($jobCard);

        $companyId = $this->companyId();

        $bookings = Booking::query()
            ->where('company_id', $companyId)
            ->latest('id')
            ->get();

        $jobs = Job::query()
            ->where('company_id', $companyId)
            ->latest('id')
            ->get(['id', 'job_code', 'client_id', 'status']);

        return view('jobcards.edit', compact('jobCard', 'bookings', 'jobs'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, JobCard $jobCard)
    {
        $this->authorizeCompany($jobCard);

        $companyId = $this->companyId();

        $data = $request->validate([
            'job_id' => [
                'required',
                Rule::exists('jobs', 'id')->where('company_id', $companyId),
            ],
            'before_photos' => ['nullable', 'array'],
            'before_photos.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'after_photos' => ['nullable', 'array'],
            'after_photos.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'mechanic' => ['required', 'string', 'max:255'],
        ]);

        $job = Job::query()
            ->where('company_id', $companyId)
            ->findOrFail($data['job_id']);

        $beforePhotos = $this->decodePhotos($jobCard->before_photos);
        $afterPhotos = $this->decodePhotos($jobCard->after_photos);

        if ($request->hasFile('before_photos')) {
            $beforePhotos = array_merge(
                $beforePhotos,
                $this->uploadPhotos($request->file('before_photos'), $companyId, $job->id)
            );
        }

        if ($request->hasFile('after_photos')) {
            $afterPhotos = array_merge(
                $afterPhotos,
                $this->uploadPhotos($request->file('after_photos'), $companyId, $job->id)
            );
        }

        $jobCard->update([
            'job_id' => $job->id,
            'before_photos' => json_encode(array_values($beforePhotos)),
            'after_photos' => json_encode(array_values($afterPhotos)),
            'notes' => $data['notes'] ?? null,
            'mechanic' => $data['mechanic'],
            'company_id' => $companyId,
        ]);

        return redirect()
            ->route('tenant.jobcards.index')
            ->with('success', 'Job card updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(JobCard $jobCard)
    {
        $this->authorizeCompany($jobCard);

        $this->deletePhotos($this->decodePhotos($jobCard->before_photos));
        $this->deletePhotos($this->decodePhotos($jobCard->after_photos));

        $jobCard->delete();

        return redirect()
            ->route('tenant.jobcards.index')
            ->with('success', 'Job card deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Photo Helpers
    |--------------------------------------------------------------------------
    */

    private function uploadPhotos($photos, int $companyId, int $jobId): array
    {
        $paths = [];

        if (! $photos) {
            return $paths;
        }

        foreach ((array) $photos as $photo) {
            if (! $photo) {
                continue;
            }

            $paths[] = $photo->store(
                "companies/{$companyId}/jobs/{$jobId}/job_photos",
                'public'
            );
        }

        return $paths;
    }

    private function decodePhotos($photos): array
    {
        if (is_array($photos)) {
            return $photos;
        }

        if (! is_string($photos) || trim($photos) === '') {
            return [];
        }

        $decoded = json_decode($photos, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function deletePhotos(array $paths): void
    {
        foreach ($paths as $path) {
            $path = trim((string) $path);

            if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
                continue;
            }

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }
}