<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job\Job;
use App\Models\Job\Invoice;
use App\Models\Job\JobCard;
use App\Models\Job\JobDocument;
use App\Models\Client\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

// Requests & Services
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Http\Requests\UploadJobDocumentRequest;
use App\Services\DocumentUploadService;
use App\Services\JobNumberService;

// Events
use App\Events\JobCompleted;

class JobController extends Controller
{
    public function __construct(
        private DocumentUploadService $uploader = new DocumentUploadService(),
        private JobNumberService $jobNumbers = new JobNumberService(),
    ) {}

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
        return $this->jobNumbers->next();
    }

    /* ---------- CRUD ---------- */

    public function index(Request $request)
    {
        $q = trim((string)$request->get('q'));
        $status = $request->get('status');

        $query = $this->companyScope()
            ->with(['client','assignedUser'])
            ->where('is_archived', false);

        if ($status && in_array($status, ['pending','in_progress','completed'], true)) {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function($w) use ($q) {
                $w->where('job_code', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%")
                  ->orWhereHas('client', fn($cq) => $cq->where('name', 'like', "%{$q}%"));
            });
        }

        $jobs = $query->latest('id')->paginate(15)->withQueryString();

        return view('admin.jobs.index', [
            'jobs'   => $jobs,
            'q'      => $q,
            'status' => $status,
        ]);
    }

    public function archived()
    {
        $jobs = $this->companyScope()
            ->with(['client','assignedUser'])
            ->where('is_archived', true)
            ->latest('id')
            ->paginate(20);

        return view('admin.jobs.archived', compact('jobs'));
    }

    public function create()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $users   = User::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        return view('admin.jobs.create', compact('clients','users'));
    }

    public function store(StoreJobRequest $request)
    {
        $data = $request->validated();
        $data['company_id']  = auth()->user()->company_id;
        $data['job_code']    = $request->input('job_code', $this->nextJobCode());
        $data['status']      = $data['status'] ?? 'pending';
        $data['is_archived'] = 0;

        if (empty($data['total_time_minutes']) && !empty($data['start_time']) && !empty($data['end_time'])) {
            $data['total_time_minutes'] = \Carbon\Carbon::parse($data['start_time'])
                ->diffInMinutes(\Carbon\Carbon::parse($data['end_time']));
        }

        $job = Job::create($data);

        if ($job->status === 'completed' && !$job->invoice) {
            Invoice::create([
                'company_id' => $job->company_id,
                'client_id'  => $job->client_id,
                'job_id'     => $job->id,
                'amount'     => 0,
                'status'     => 'pending',
                'due_date'   => now()->addDays(7),
                'source'     => 'generated',
                'number'     => null,
                'currency'   => 'AED',
            ]);
        }

        if ($job->status === 'completed') {
            DB::afterCommit(fn() => event(new JobCompleted($job->fresh())));
        }

        return redirect()->route('admin.jobs.show', $job)->with('success', 'Job created successfully.');
    }

    public function show(Job $job)
    {
        $this->authorizeCompany($job);
        $job->load(['client','assignedUser','invoices','jobCards']);
        return view('admin.jobs.show', compact('job'));
    }

    public function edit(Job $job)
    {
        $this->authorizeCompany($job);
        $clients = Client::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $users   = User::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        return view('admin.jobs.edit', compact('job','clients','users'));
    }

    public function update(UpdateJobRequest $request, Job $job)
    {
        $this->authorizeCompany($job);
        $data = $request->validated();

        if (empty($data['total_time_minutes']) && !empty($data['start_time']) && !empty($data['end_time'])) {
            $data['total_time_minutes'] = \Carbon\Carbon::parse($data['start_time'])
                ->diffInMinutes(\Carbon\Carbon::parse($data['end_time']));
        }

        $oldStatus = $job->status;
        $job->update($data);

        if ($job->status === 'completed' && !$job->invoice) {
            Invoice::create([
                'company_id' => $job->company_id,
                'client_id'  => $job->client_id,
                'job_id'     => $job->id,
                'amount'     => 0,
                'status'     => 'pending',
                'due_date'   => now()->addDays(7),
                'source'     => 'generated',
                'currency'   => 'AED',
            ]);
        }

        if ($oldStatus !== 'completed' && $job->status === 'completed') {
            DB::afterCommit(fn() => event(new JobCompleted($job->fresh())));
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

    /* ---------- Upload original Job Card (PDF/Image) ---------- */
    public function uploadCard(UploadJobDocumentRequest $request, Job $job)
    {
        $this->authorizeCompany($job);

        $meta = $this->uploader->store(
            $request->file('file'),
            'companies/'.$job->company_id.'/jobs/'.$job->id.'/job_card'
        );

        JobCard::create([
            'job_id'        => $job->id,
            'company_id'    => $job->company_id,
            'description'   => $request->input('description'),
            'status'        => 'uploaded',
            'file_path'     => $meta['path'],
            'file_type'     => $meta['mime'],
            'assigned_to'   => auth()->id(),
        ]);

        JobDocument::create([
            'client_id'     => $job->client_id,
            'job_id'        => $job->id,
            'type'          => 'job_card',
            'source'        => 'upload',
            'sender_phone'  => null,
            'sender_email'  => null,
            'hash'          => $meta['hash'],
            'original_name' => $meta['original_name'],
            'mime'          => $meta['mime'],
            'size'          => $meta['size'],
            'path'          => $meta['path'],
            'url'           => $meta['url'],
            'status'        => 'assigned',
            'received_at'   => now(),
        ]);

        return back()->with('success', 'Job card uploaded.');
    }
}
