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

use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Http\Requests\UploadJobDocumentRequest;
use App\Services\DocumentUploadService;
use App\Services\JobNumberService;

use App\Events\JobCompleted;

class JobController extends Controller
{
    public function __construct(
        private DocumentUploadService $uploader = new DocumentUploadService(),
        private JobNumberService $jobNumbers = new JobNumberService(),
    ) {}

    protected function authorizeCompany(Job $job): void
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

    /* ================= Index ================= */

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $status = $request->get('status');

        $query = $this->companyScope()
            ->with(['client:id,name', 'assignedUser:id,name'])
            ->where('is_archived', false);

        if (in_array($status, ['pending','in_progress','completed'], true)) {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('job_code', 'like', "%{$q}%")
                  ->orWhere('description', 'like', "%{$q}%")
                  ->orWhereHas('client', fn ($c) =>
                      $c->where('name', 'like', "%{$q}%")
                  );
            });
        }

        $jobs = $query->latest('id')->paginate(15)->withQueryString();

        return view('admin.jobs.index', compact('jobs','q','status'));
    }

    /* ================= Create ================= */

    public function create()
    {
        $companyId = auth()->user()->company_id;

        $clients = Client::where('company_id', $companyId)->orderBy('name')->get(['id','name']);
        $users   = User::where('company_id', $companyId)->orderBy('name')->get(['id','name']);

        return view('admin.jobs.create', compact('clients','users'));
    }

    /* ================= Store ================= */

    public function store(StoreJobRequest $request)
    {
        $data = $request->validated();

        $data['company_id']  = auth()->user()->company_id;
        $data['job_code']    = $data['job_code'] ?? $this->nextJobCode();
        $data['status']      = $data['status'] ?? 'pending';
        $data['is_archived'] = false;

        if (
            empty($data['total_time_minutes']) &&
            !empty($data['start_time']) &&
            !empty($data['end_time'])
        ) {
            $data['total_time_minutes'] =
                now()->parse($data['start_time'])
                    ->diffInMinutes(now()->parse($data['end_time']));
        }

        $job = Job::create($data);

        if ($job->status === 'completed') {
            $this->ensureInvoice($job);
            DB::afterCommit(fn () => event(new JobCompleted($job->fresh())));
        }

        return redirect()
            ->route('admin.jobs.show', $job)
            ->with('success', 'Job created successfully.');
    }

    /* ================= Show ================= */

    public function show(Job $job)
    {
        $this->authorizeCompany($job);

        $job->load([
            'client',
            'assignedUser',
            'invoices',
            'jobCards',
            'jobDocuments',
        ]);

        return view('admin.jobs.show', compact('job'));
    }

    /* ================= Edit ================= */

    public function edit(Job $job)
    {
        $this->authorizeCompany($job);

        $companyId = auth()->user()->company_id;

        $clients = Client::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id','name']);

        $users = User::where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id','name']);

        return view('admin.jobs.edit', compact('job','clients','users'));
    }

    /* ================= Update ================= */

    public function update(UpdateJobRequest $request, Job $job)
    {
        $this->authorizeCompany($job);

        $data = $request->validated();

        if (
            empty($data['total_time_minutes']) &&
            !empty($data['start_time']) &&
            !empty($data['end_time'])
        ) {
            $data['total_time_minutes'] =
                now()->parse($data['start_time'])
                    ->diffInMinutes(now()->parse($data['end_time']));
        }

        $oldStatus = $job->status;

        $job->update($data);

        if ($oldStatus !== 'completed' && $job->status === 'completed') {
            $this->ensureInvoice($job);
            DB::afterCommit(fn () => event(new JobCompleted($job->fresh())));
        }

        return redirect()
            ->route('admin.jobs.show', $job)
            ->with('success', 'Job updated successfully.');
    }

    /* ================= Archive ================= */

    public function archive(Job $job)
    {
        $this->authorizeCompany($job);

        $job->update(['is_archived' => true]);

        return back()->with('success', 'Job archived.');
    }

    public function restore(Job $job)
    {
        $this->authorizeCompany($job);

        $job->update(['is_archived' => false]);

        return back()->with('success', 'Job restored.');
    }

    /* ================= Upload Job Card ================= */

    public function uploadCard(UploadJobDocumentRequest $request, Job $job)
    {
        $this->authorizeCompany($job);

        $meta = $this->uploader->store(
            $request->file('file'),
            "companies/{$job->company_id}/jobs/{$job->id}/job_cards"
        );

        JobCard::create([
            'job_id'      => $job->id,
            'company_id'  => $job->company_id,
            'description' => $request->input('description'),
            'status'      => 'uploaded',
            'file_path'   => $meta['path'],
            'file_type'   => $meta['mime'],
            'assigned_to' => auth()->id(),
        ]);

        JobDocument::create([
            'client_id'     => $job->client_id,
            'job_id'        => $job->id,
            'type'          => 'job_card',
            'source'        => 'upload',
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

    /* ================= Helpers ================= */

    protected function ensureInvoice(Job $job): void
    {
        if ($job->invoice) {
            return;
        }

        Invoice::create([
            'company_id' => $job->company_id,
            'client_id'  => $job->client_id,
            'job_id'     => $job->id,
            'amount'     => 0,
            'status'     => 'pending',
            'due_date'   => now()->addDays(7),
            'currency'   => 'AED',
            'source'     => 'generated',
        ]);
    }
}