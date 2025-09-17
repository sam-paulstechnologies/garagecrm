<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job\Job;
use App\Models\Job\Invoice; // <-- for auto-create invoice
use App\Models\Client\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// 🔔 Events
use App\Events\JobCompleted;

class JobController extends Controller
{
    /* ---------- Utilities ---------- */
    protected function authorizeCompany(Job $job)
    {
        abort_if($job->company_id !== auth()->user()->company_id, 403);
    }

    protected function companyScope()
    {
        return Job::where('company_id', auth()->user()->company_id);
    }

    protected function nextJobCode(): string
    {
        $lastId = Job::max('id') ?? 0;
        return 'JOB-' . now()->format('Y') . '-' . str_pad($lastId + 1, 6, '0', STR_PAD_LEFT);
    }

    /* ---------- CRUD ---------- */
    public function index()
    {
        $jobs = $this->companyScope()
            ->with(['client', 'assignedUser'])
            ->where('is_archived', false)
            ->latest('id')
            ->paginate(20);

        return view('admin.jobs.index', compact('jobs'));
    }

    public function archived()
    {
        $jobs = $this->companyScope()
            ->with(['client', 'assignedUser'])
            ->where('is_archived', true)
            ->latest('id')
            ->paginate(20);

        return view('admin.jobs.archived', compact('jobs'));
    }

    public function create()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $users   = User::where('company_id', auth()->user()->company_id)->orderBy('name')->get();

        return view('admin.jobs.create', compact('clients', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'          => ['required', 'exists:clients,id'],
            'booking_id'         => ['nullable', 'integer'],
            'description'        => ['required', 'string', 'max:1000'],
            'start_time'         => ['nullable', 'date'],
            'end_time'           => ['nullable', 'date', 'after_or_equal:start_time'],
            'work_summary'       => ['nullable', 'string'],
            'issues_found'       => ['nullable', 'string'],
            'parts_used'         => ['nullable', 'string'],
            'total_time_minutes' => ['nullable', 'integer', 'min:0'],
            'status'             => ['nullable', 'in:pending,in_progress,completed'],
            'assigned_to'        => ['nullable', 'exists:users,id'],
        ]);

        $data['company_id']  = auth()->user()->company_id;
        $data['job_code']    = $request->filled('job_code') ? $request->job_code : $this->nextJobCode();
        $data['status']      = $data['status'] ?? 'pending';
        $data['is_archived'] = 0;

        if (empty($data['total_time_minutes']) && !empty($data['start_time']) && !empty($data['end_time'])) {
            $data['total_time_minutes'] = \Carbon\Carbon::parse($data['start_time'])
                ->diffInMinutes(\Carbon\Carbon::parse($data['end_time']));
        }

        $job = Job::create($data);

        // 🔹 Auto-create invoice if created as completed (edge case)
        if ($job->status === 'completed' && !$job->invoice) {
            Invoice::create([
                'company_id' => $job->company_id,
                'client_id'  => $job->client_id,
                'job_id'     => $job->id,
                'amount'     => 0,
                'status'     => 'pending',
                'due_date'   => now()->addDays(7),
            ]);
        }

        // 🔔 Fire JobCompleted if created as completed
        if ($job->status === 'completed') {
            DB::afterCommit(function () use ($job) {
                event(new JobCompleted($job->fresh()));
            });
        }

        return redirect()->route('admin.jobs.show', $job)->with('success', 'Job created successfully.');
    }

    public function show(Job $job)
    {
        $this->authorizeCompany($job);
        return view('admin.jobs.show', compact('job'));
    }

    public function edit(Job $job)
    {
        $this->authorizeCompany($job);
        $clients = Client::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $users   = User::where('company_id', auth()->user()->company_id)->orderBy('name')->get();

        return view('admin.jobs.edit', compact('job', 'clients', 'users'));
    }

    public function update(Request $request, Job $job)
    {
        $this->authorizeCompany($job);

        $data = $request->validate([
            'client_id'          => ['required', 'exists:clients,id'],
            'booking_id'         => ['nullable', 'integer'],
            'description'        => ['required', 'string', 'max:1000'],
            'start_time'         => ['nullable', 'date'],
            'end_time'           => ['nullable', 'date', 'after_or_equal:start_time'],
            'work_summary'       => ['nullable', 'string'],
            'issues_found'       => ['nullable', 'string'],
            'parts_used'         => ['nullable', 'string'],
            'total_time_minutes' => ['nullable', 'integer', 'min:0'],
            'status'             => ['required', 'in:pending,in_progress,completed'],
            'assigned_to'        => ['nullable', 'exists:users,id'],
        ]);

        if (empty($data['total_time_minutes']) && !empty($data['start_time']) && !empty($data['end_time'])) {
            $data['total_time_minutes'] = \Carbon\Carbon::parse($data['start_time'])
                ->diffInMinutes(\Carbon\Carbon::parse($data['end_time']));
        }

        $oldStatus = $job->status;

        $job->update($data);

        // 🔹 Auto-create invoice when job becomes completed
        if ($job->status === 'completed' && !$job->invoice) {
            Invoice::create([
                'company_id' => $job->company_id,
                'client_id'  => $job->client_id,
                'job_id'     => $job->id,
                'amount'     => 0,                 // placeholder
                'status'     => 'pending',
                'due_date'   => now()->addDays(7), // default
            ]);
        }

        // 🔔 Fire JobCompleted only when status transitioned to completed
        if ($oldStatus !== 'completed' && $job->status === 'completed') {
            DB::afterCommit(function () use ($job) {
                event(new JobCompleted($job->fresh()));
            });
        }

        return redirect()->route('admin.jobs.show', $job)->with('success', 'Job updated successfully.');
    }

    public function archive(Job $job)
    {
        $this->authorizeCompany($job);
        $job->update(['is_archived' => 1]);
        return back()->with('success', 'Job archived.');
    }

    public function restore(Job $job)
    {
        $this->authorizeCompany($job);
        $job->update(['is_archived' => 0]);
        return back()->with('success', 'Job restored.');
    }

    public function destroy(Job $job)
    {
        $this->authorizeCompany($job);
        $job->delete();
        return redirect()->route('admin.jobs.index')->with('success', 'Job deleted successfully.');
    }
}
