<?php

namespace App\Http\Controllers\Admin;

use App\Events\JobCompleted;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Http\Requests\UploadJobDocumentRequest;
use App\Models\Client\Client;
use App\Models\Job\Booking;
use App\Models\Job\Invoice;
use App\Models\Job\Job;
use App\Models\Job\JobCard;
use App\Models\Job\JobDocument;
use App\Models\User;
use App\Services\DocumentUploadService;
use App\Services\JobNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JobController extends Controller
{
    public function __construct(
        private DocumentUploadService $uploader = new DocumentUploadService(),
        private JobNumberService $jobNumbers = new JobNumberService(),
    ) {}

    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    protected function authorizeCompany(Job $job): void
    {
        abort_unless((int) $job->company_id === $this->companyId(), 404);
    }

    protected function companyScope()
    {
        return Job::query()->where('company_id', $this->companyId());
    }

    protected function nextJobCode(): string
    {
        return $this->jobNumbers->next();
    }

    /*
    |--------------------------------------------------------------------------
    | Index - Open Jobs Only
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $companyId = $this->companyId();

        $q = trim((string) $request->get('q'));
        $status = $request->get('status');

        $openStatuses = ['pending', 'in_progress'];

        $query = $this->companyScope()
            ->with([
                'client' => fn ($clientQuery) => $clientQuery->where('company_id', $companyId),
                'assignedUser:id,name,company_id',
                'invoice' => fn ($invoiceQuery) => $invoiceQuery->where('company_id', $companyId),
                'invoices' => fn ($invoiceQuery) => $invoiceQuery->where('company_id', $companyId),
            ])
            ->where('is_archived', false)
            ->whereIn('status', $openStatuses);

        if (in_array($status, $openStatuses, true)) {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($where) use ($q, $companyId) {
                $where
                    ->where('job_code', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('work_summary', 'like', "%{$q}%")
                    ->orWhere('issues_found', 'like', "%{$q}%")
                    ->orWhere('parts_used', 'like', "%{$q}%")
                    ->orWhereHas('client', function ($clientQuery) use ($q, $companyId) {
                        $clientQuery
                            ->where('company_id', $companyId)
                            ->where(function ($clientSearch) use ($q) {
                                $clientSearch
                                    ->where('name', 'like', "%{$q}%")
                                    ->orWhere('phone', 'like', "%{$q}%")
                                    ->orWhere('email', 'like', "%{$q}%");
                            });
                    });
            });
        }

        $jobs = $query
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $base = $this->companyScope()
            ->where('is_archived', false);

        $stats = [
            'open_jobs' => (clone $base)->whereIn('status', $openStatuses)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
        ];

        return view('admin.jobs.index', compact('jobs', 'q', 'status', 'stats'));
    }

    /*
    |--------------------------------------------------------------------------
    | Completed Jobs
    |--------------------------------------------------------------------------
    */

    public function completed(Request $request)
    {
        $companyId = $this->companyId();

        $q = trim((string) $request->get('q'));

        $query = $this->companyScope()
            ->with([
                'client' => fn ($clientQuery) => $clientQuery->where('company_id', $companyId),
                'invoice' => fn ($invoiceQuery) => $invoiceQuery->where('company_id', $companyId),
                'invoices' => fn ($invoiceQuery) => $invoiceQuery->where('company_id', $companyId),
            ])
            ->where('is_archived', false)
            ->where('status', 'completed');

        if ($q !== '') {
            $query->where(function ($where) use ($q, $companyId) {
                $where
                    ->where('job_code', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('work_summary', 'like', "%{$q}%")
                    ->orWhere('issues_found', 'like', "%{$q}%")
                    ->orWhere('parts_used', 'like', "%{$q}%")
                    ->orWhereHas('client', function ($clientQuery) use ($q, $companyId) {
                        $clientQuery
                            ->where('company_id', $companyId)
                            ->where(function ($clientSearch) use ($q) {
                                $clientSearch
                                    ->where('name', 'like', "%{$q}%")
                                    ->orWhere('phone', 'like', "%{$q}%")
                                    ->orWhere('email', 'like', "%{$q}%");
                            });
                    });
            });
        }

        $jobs = $query
            ->latest('updated_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.jobs.completed', compact('jobs', 'q'));
    }

    /*
    |--------------------------------------------------------------------------
    | Archived
    |--------------------------------------------------------------------------
    */

    public function archived(Request $request)
    {
        $companyId = $this->companyId();

        $q = trim((string) $request->get('q'));
        $status = $request->get('status');

        $query = $this->companyScope()
            ->with([
                'client' => fn ($clientQuery) => $clientQuery->where('company_id', $companyId),
                'assignedUser:id,name,company_id',
            ])
            ->where('is_archived', true);

        if (in_array($status, ['pending', 'in_progress', 'completed'], true)) {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($where) use ($q, $companyId) {
                $where
                    ->where('job_code', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('client', function ($clientQuery) use ($q, $companyId) {
                        $clientQuery
                            ->where('company_id', $companyId)
                            ->where('name', 'like', "%{$q}%");
                    });
            });
        }

        $jobs = $query
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.jobs.index', compact('jobs', 'q', 'status'));
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $companyId = $this->companyId();

        $clients = Client::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.jobs.create', compact('clients', 'users'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store
    |--------------------------------------------------------------------------
    */

    public function store(StoreJobRequest $request)
    {
        $companyId = $this->companyId();

        $data = $request->validated();

        $this->validateClientAndAssignee($data, $companyId);

        if (! empty($data['booking_id'])) {
            $booking = Booking::query()
                ->where('company_id', $companyId)
                ->findOrFail($data['booking_id']);

            abort_unless((int) $booking->client_id === (int) $data['client_id'], 422);
        }

        $data['company_id'] = $companyId;
        $data['job_code'] = $data['job_code'] ?? $this->nextJobCode();
        $data['status'] = $data['status'] ?? 'pending';
        $data['is_archived'] = false;

        $job = Job::create($data);

        if ($this->normalizeStatus((string) $job->status) === 'completed') {
            $this->ensureInvoice($job);
            $this->dispatchJobCompleted($job);
        }

        return redirect()
            ->route('admin.jobs.show', $job)
            ->with('success', 'Job created successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(Job $job)
    {
        $this->authorizeCompany($job);

        $companyId = $this->companyId();

        $job->load([
            'client' => fn ($clientQuery) => $clientQuery->where('company_id', $companyId),
            'assignedUser:id,name,company_id',
            'invoice' => fn ($invoiceQuery) => $invoiceQuery->where('company_id', $companyId),
            'invoices' => fn ($invoiceQuery) => $invoiceQuery->where('company_id', $companyId),
        ]);

        return view('admin.jobs.show', compact('job'));
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(Job $job)
    {
        $this->authorizeCompany($job);

        $companyId = $this->companyId();

        $clients = Client::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $users = User::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $job->load([
            'invoice' => fn ($invoiceQuery) => $invoiceQuery->where('company_id', $companyId),
            'invoices' => fn ($invoiceQuery) => $invoiceQuery->where('company_id', $companyId),
        ]);

        return view('admin.jobs.edit', compact('job', 'clients', 'users'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(UpdateJobRequest $request, Job $job)
    {
        $this->authorizeCompany($job);

        $companyId = $this->companyId();

        $validated = $request->validated();

        $this->validateClientAndAssignee($validated, $companyId);

        if (! empty($validated['booking_id'])) {
            $booking = Booking::query()
                ->where('company_id', $companyId)
                ->findOrFail($validated['booking_id']);

            abort_unless((int) $booking->client_id === (int) $validated['client_id'], 422);
        }

        $oldStatus = $this->normalizeStatus((string) $job->status);
        $newStatus = $this->normalizeStatus((string) ($validated['status'] ?? $job->status));

        $invoiceNumber = $validated['invoice_number'] ?? null;
        $invoiceAmount = $validated['invoice_amount'] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Invoice fields should not go into jobs table
        |--------------------------------------------------------------------------
        */

        unset($validated['invoice_number'], $validated['invoice_amount']);

        DB::transaction(function () use ($job, $validated, $newStatus, $invoiceNumber, $invoiceAmount, $companyId) {
            $job->update($validated);

            if ($newStatus === 'completed') {
                $invoice = $job->invoice()
                    ->where('company_id', $companyId)
                    ->first() ?: new Invoice();

                $invoice->company_id = $job->company_id;
                $invoice->client_id = $job->client_id;
                $invoice->job_id = $job->id;
                $invoice->amount = $invoiceAmount ?? $invoice->amount ?? 0;
                $invoice->status = 'paid';
                $invoice->due_date = now();
                $invoice->currency = 'AED';
                $invoice->source = 'generated';

                if (Schema::hasColumn('invoices', 'invoice_number')) {
                    $invoice->invoice_number = $invoiceNumber;
                }

                if (Schema::hasColumn('invoices', 'number')) {
                    $invoice->number = $invoiceNumber;
                }

                $invoice->save();
            }
        });

        /*
        |--------------------------------------------------------------------------
        | JobCompleted event
        |--------------------------------------------------------------------------
        |
        | JobObserver no longer dispatches this event to avoid duplicate
        | WhatsApp feedback messages.
        |
        | This controller is now the authoritative source for admin-driven
        | job completion.
        |
        */

        if ($oldStatus !== 'completed' && $newStatus === 'completed') {
            $this->dispatchJobCompleted($job);
        }

        return redirect()
            ->route('admin.jobs.show', $job)
            ->with('success', 'Job updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Archive / Restore
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Upload Job Card - kept for compatibility, not shown in UI
    |--------------------------------------------------------------------------
    */

    public function uploadCard(UploadJobDocumentRequest $request, Job $job)
    {
        $this->authorizeCompany($job);

        $meta = $this->uploader->store(
            $request->file('file'),
            "companies/{$job->company_id}/jobs/{$job->id}/job_cards"
        );

        JobCard::create([
            'job_id' => $job->id,
            'company_id' => $job->company_id,
            'description' => $request->input('description'),
            'status' => 'uploaded',
            'file_path' => $meta['path'],
            'file_type' => $meta['mime'],
            'assigned_to' => auth()->id(),
        ]);

        JobDocument::create([
            'company_id' => $job->company_id,
            'client_id' => $job->client_id,
            'job_id' => $job->id,
            'type' => 'job_card',
            'source' => 'upload',
            'hash' => $meta['hash'],
            'original_name' => $meta['original_name'],
            'mime' => $meta['mime'],
            'size' => $meta['size'],
            'path' => $meta['path'],
            'url' => $meta['url'],
            'status' => 'assigned',
            'received_at' => now(),
        ]);

        return back()->with('success', 'Job card uploaded.');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function validateClientAndAssignee(array $data, int $companyId): void
    {
        if (! empty($data['client_id'])) {
            Client::query()
                ->where('company_id', $companyId)
                ->findOrFail($data['client_id']);
        }

        if (! empty($data['assigned_to'])) {
            User::query()
                ->where('company_id', $companyId)
                ->findOrFail($data['assigned_to']);
        }
    }

    protected function ensureInvoice(Job $job): void
    {
        $companyId = (int) $job->company_id;

        $invoice = $job->invoice()
            ->where('company_id', $companyId)
            ->first();

        if ($invoice) {
            return;
        }

        Invoice::create([
            'company_id' => $companyId,
            'client_id' => $job->client_id,
            'job_id' => $job->id,
            'amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addDays(7),
            'currency' => 'AED',
            'source' => 'generated',
        ]);
    }

    protected function dispatchJobCompleted(Job $job): void
    {
        $freshJob = $job->fresh();

        if (! $freshJob) {
            return;
        }

        $this->authorizeCompany($freshJob);

        $invoiceUrl = null;

        if (method_exists($freshJob, 'invoiceUrl')) {
            $invoiceUrl = $freshJob->invoiceUrl();
        } elseif (! empty($freshJob->invoice_url)) {
            $invoiceUrl = $freshJob->invoice_url;
        }

        event(new JobCompleted($freshJob, $invoiceUrl));
    }

    protected function normalizeStatus(string $status): string
    {
        return strtolower(trim($status));
    }
}