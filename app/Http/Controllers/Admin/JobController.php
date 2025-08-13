<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job\Job;
use App\Models\Client\Client;
use App\Models\User;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index()
    {
        $jobs = Job::with(['client', 'assignedUser'])
            ->where('company_id', auth()->user()->company_id)
            ->latest()
            ->paginate(20);

        return view('admin.jobs.index', compact('jobs'));
    }

    public function create()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        $users = User::all();

        return view('admin.jobs.create', compact('clients', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'description' => 'nullable|string',
            'status'      => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $data['company_id'] = auth()->user()->company_id;

        Job::create($data);

        return redirect()->route('admin.jobs.index')->with('success', 'Job created successfully.');
    }

    public function edit(Job $job)
    {
        $this->authorizeCompany($job);

        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        $users = User::all();

        return view('admin.jobs.edit', compact('job', 'clients', 'users'));
    }

    public function update(Request $request, Job $job)
    {
        $this->authorizeCompany($job);

        $data = $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'description' => 'nullable|string',
            'status'      => 'required|string',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $job->update($data);

        return redirect()->route('admin.jobs.index')->with('success', 'Job updated successfully.');
    }

    public function destroy(Job $job)
    {
        $this->authorizeCompany($job);
        $job->delete();

        return redirect()->route('admin.jobs.index')->with('success', 'Job deleted successfully.');
    }

    protected function authorizeCompany(Job $job)
    {
        abort_if($job->company_id !== auth()->user()->company_id, 403);
    }
}
