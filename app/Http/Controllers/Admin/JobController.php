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
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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

        $q = trim((string) $request->get('q', ''));
        $status = (string) $request->get('status', '');
        $bucket = (string) $request->get('bucket', '');

        $openStatuses = ['pending', 'in_progress'];

        $jobFilters = [
            'date_range' => $request->get('date_range', 'all_time'),
            'lead_source' => $request->get('lead_source', 'all'),
            'assigned_user' => $request->get('assigned_user', 'all'),
            'service_type' => $request->get('service_type', 'all'),
            'customer_type' => $request->get('customer_type', 'all'),
            'from_date' => $request->get('from_date'),
            'to_date' => $request->get('to_date'),
        ];

        $assignedUsers = User::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $query = $this->companyScope()
            ->with([
                'client' => fn ($clientQuery) => $clientQuery->where('company_id', $companyId),
                'assignedUser:id,name,company_id',
                'invoice' => fn ($invoiceQuery) => $invoiceQuery->where('company_id', $companyId),
                'invoices' => fn ($invoiceQuery) => $invoiceQuery->where('company_id', $companyId),
            ])
            ->where('is_archived', false)
            ->whereIn('status', $openStatuses);

        $this->applyJobIndexFilters($query, $request, $companyId, $openStatuses);

        if (in_array($status, $openStatuses, true)) {
            $query->where('status', $status);
        }

        if ($bucket !== '') {
            $this->applyJobBucketFilter($query, $bucket);
        }

        $jobs = $query
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $base = $this->companyScope()
            ->where('is_archived', false)
            ->whereIn('status', $openStatuses);

        $this->applyJobIndexFilters($base, $request, $companyId, $openStatuses, false);

        $stats = [
            'open_jobs' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'in_progress' => (clone $base)->where('status', 'in_progress')->count(),
        ];

        $bucketCounts = $this->buildJobBucketCounts((clone $base)->get());
        [$jobPageTitle, $jobPageSubtitle] = $this->jobIndexHeading($status, $bucket, $q);

        return view('admin.jobs.index', compact(
            'jobs',
            'q',
            'status',
            'bucket',
            'stats',
            'bucketCounts',
            'jobFilters',
            'assignedUsers',
            'jobPageTitle',
            'jobPageSubtitle'
        ));
    }

    protected function jobIndexHeading(string $status, string $bucket, string $q = ''): array
    {
        $title = 'Open Jobs';
        $subtitle = 'Track active service jobs, customer updates, service buckets, and closure readiness.';

        if ($bucket !== '') {
            $bucketTitle = ucwords(str_replace('_', ' ', $bucket));
            $title = "{$bucketTitle} Jobs";
            $subtitle = 'Open jobs filtered by the selected service bucket.';
        }

        if ($status !== '') {
            [$title, $subtitle] = match ($status) {
                'pending' => ['Pending Jobs', 'Jobs waiting to start or awaiting technician action.'],
                'in_progress' => ['In Progress Jobs', 'Jobs currently being worked on by the service team.'],
                default => [ucwords(str_replace('_', ' ', $status)) . ' Jobs', 'Jobs filtered by the selected status.'],
            };
        }

        if ($q !== '') {
            $subtitle .= ' Search: "' . str($q)->limit(40) . '".';
        }

        return [$title, $subtitle];
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

        $invoiceNumber = $data['invoice_number'] ?? null;
        $invoiceAmount = $data['invoice_amount'] ?? null;

        unset($data['invoice_number'], $data['invoice_amount']);

        if (! empty($data['booking_id'])) {
            $booking = Booking::query()
                ->where('company_id', $companyId)
                ->findOrFail($data['booking_id']);

            abort_unless((int) $booking->client_id === (int) $data['client_id'], 422);

            $data = $this->applyJourneyLinksFromBooking($data, $booking);
        }

        $data['company_id'] = $companyId;
        $data['job_code'] = $data['job_code'] ?? $this->nextJobCode();
        $data['status'] = $data['status'] ?? 'pending';
        $data['is_archived'] = false;

        $job = Job::create($data);

        if ($this->normalizeStatus((string) $job->status) === 'completed') {
            $this->createOrUpdateCompletionInvoice($job, $invoiceNumber, $invoiceAmount, 'paid');
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

            $validated = $this->applyJourneyLinksFromBooking($validated, $booking);
        }

        $oldStatus = $this->normalizeStatus((string) $job->status);
        $newStatus = $this->normalizeStatus((string) ($validated['status'] ?? $job->status));

        $invoiceNumber = $validated['invoice_number'] ?? null;
        $invoiceAmount = $validated['invoice_amount'] ?? null;

        unset($validated['invoice_number'], $validated['invoice_amount']);

        DB::transaction(function () use ($job, $validated, $newStatus, $invoiceNumber, $invoiceAmount, $companyId) {
            $job->update($validated);

            if ($newStatus === 'completed') {
                $this->createOrUpdateCompletionInvoice($job, $invoiceNumber, $invoiceAmount, 'paid');
            }
        });

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
    | Index Helpers
    |--------------------------------------------------------------------------
    */

    protected function applyJobIndexFilters(
        Builder $query,
        Request $request,
        int $companyId,
        array $openStatuses,
        bool $includeStatus = true
    ): void {
        $q = trim((string) $request->get('q', ''));
        $status = (string) $request->get('status', '');
        $dateRange = (string) $request->get('date_range', 'all_time');
        $leadSource = (string) $request->get('lead_source', 'all');
        $assignedUser = (string) $request->get('assigned_user', 'all');
        $serviceType = (string) $request->get('service_type', 'all');
        $customerType = (string) $request->get('customer_type', 'all');

        if ($includeStatus && in_array($status, $openStatuses, true)) {
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

        [$fromDate, $toDate, $applyDateFilter] = $this->resolveJobDateRange($request, $dateRange);

        if ($applyDateFilter && Schema::hasColumn('jobs', 'created_at')) {
            $query->whereBetween('created_at', [$fromDate, $toDate]);
        }

        if ($leadSource !== 'all') {
            $sourceColumn = $this->firstExistingJobColumn(['lead_source', 'source', 'source_type', 'channel']);

            if ($sourceColumn) {
                $query->where($sourceColumn, $leadSource);
            }
        }

        if ($assignedUser !== 'all') {
            $assignedColumn = $this->firstExistingJobColumn(['assigned_to', 'assigned_user_id', 'user_id', 'owner_id']);

            if ($assignedColumn) {
                $query->where($assignedColumn, $assignedUser);
            }
        }

        if ($serviceType !== 'all') {
            $this->applyServiceTextFilter($query, $serviceType);
        }

        if ($customerType !== 'all' && Schema::hasColumn('jobs', 'client_id')) {
            $query->whereIn('client_id', function ($subQuery) use ($customerType, $fromDate, $toDate) {
                $subQuery->select('id')->from('clients');

                if ($customerType === 'new') {
                    $subQuery->whereBetween('created_at', [$fromDate, $toDate]);
                }

                if (in_array($customerType, ['returning', 'existing'], true)) {
                    $subQuery->where('created_at', '<', $fromDate);
                }

                if (in_array($customerType, ['fleet', 'corporate'], true)) {
                    $subQuery->where(function ($clientQuery) use ($customerType) {
                        $clientQuery
                            ->where('customer_type', $customerType)
                            ->orWhere('type', $customerType)
                            ->orWhere('source', $customerType);
                    });
                }
            });
        }
    }

    protected function resolveJobDateRange(Request $request, string $range): array
    {
        return match ($range) {
            'today' => [
                now()->startOfDay(),
                now()->endOfDay(),
                true,
            ],

            'yesterday' => [
                now()->subDay()->startOfDay(),
                now()->subDay()->endOfDay(),
                true,
            ],

            'last_7_days' => [
                now()->subDays(6)->startOfDay(),
                now()->endOfDay(),
                true,
            ],

            'this_month' => [
                now()->startOfMonth()->startOfDay(),
                now()->endOfDay(),
                true,
            ],

            'last_month' => [
                now()->subMonthNoOverflow()->startOfMonth()->startOfDay(),
                now()->subMonthNoOverflow()->endOfMonth()->endOfDay(),
                true,
            ],

            'custom' => [
                $request->filled('from_date')
                    ? Carbon::parse($request->get('from_date'))->startOfDay()
                    : now()->startOfMonth()->startOfDay(),

                $request->filled('to_date')
                    ? Carbon::parse($request->get('to_date'))->endOfDay()
                    : now()->endOfDay(),

                true,
            ],

            default => [
                Carbon::create(1970, 1, 1)->startOfDay(),
                now()->endOfDay(),
                false,
            ],
        };
    }

    protected function firstExistingJobColumn(array $columns): ?string
    {
        foreach ($columns as $column) {
            if (Schema::hasColumn('jobs', $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function applyJobBucketFilter(Builder $query, string $bucket): void
    {
        match ($bucket) {
            'oil' => $this->applyServiceTextFilter($query, 'oil'),
            'battery' => $this->applyServiceTextFilter($query, 'battery'),
            'tyres' => $this->applyServiceTextFilter($query, 'tyres'),
            'ac' => $this->applyServiceTextFilter($query, 'ac'),
            'brakes' => $this->applyServiceTextFilter($query, 'brakes'),
            'wash' => $this->applyServiceTextFilter($query, 'wash'),
            'general' => null,
            default => null,
        };
    }

    protected function applyServiceTextFilter(Builder $query, string $serviceType): void
    {
        $keywords = match ($serviceType) {
            'oil' => ['oil'],
            'battery' => ['battery'],
            'tyres' => ['tyre', 'tire'],
            'ac' => ['ac', 'a/c', 'air condition'],
            'brakes' => ['brake'],
            'wash' => ['wash', 'detailing'],
            'general_service' => ['general', 'service'],
            default => [$serviceType],
        };

        $query->where(function ($where) use ($keywords) {
            foreach ($keywords as $keyword) {
                $where
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->orWhere('work_summary', 'like', "%{$keyword}%")
                    ->orWhere('issues_found', 'like', "%{$keyword}%")
                    ->orWhere('parts_used', 'like', "%{$keyword}%");
            }
        });
    }

    protected function buildJobBucketCounts($jobs): array
    {
        $counts = [
            'General Service' => 0,
            'Oil Service' => 0,
            'Battery Service' => 0,
            'Tyre Service' => 0,
            'AC Service' => 0,
            'Brake Service' => 0,
            'Car Wash / Detailing' => 0,
        ];

        foreach ($jobs as $job) {
            $signal = $this->detectJobServiceSignal($job);
            $counts[$signal] = ($counts[$signal] ?? 0) + 1;
        }

        return $counts;
    }

    protected function detectJobServiceSignal($job): string
    {
        $jobText = strtolower(trim(
            ($job->description ?? '') . ' ' .
            ($job->work_summary ?? '') . ' ' .
            ($job->issues_found ?? '') . ' ' .
            ($job->parts_used ?? '')
        ));

        if (str_contains($jobText, 'oil')) {
            return 'Oil Service';
        }

        if (str_contains($jobText, 'battery')) {
            return 'Battery Service';
        }

        if (str_contains($jobText, 'tyre') || str_contains($jobText, 'tire')) {
            return 'Tyre Service';
        }

        if (str_contains($jobText, 'ac') || str_contains($jobText, 'a/c') || str_contains($jobText, 'air condition')) {
            return 'AC Service';
        }

        if (str_contains($jobText, 'brake')) {
            return 'Brake Service';
        }

        if (str_contains($jobText, 'wash') || str_contains($jobText, 'detailing')) {
            return 'Car Wash / Detailing';
        }

        return 'General Service';
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

    protected function applyJourneyLinksFromBooking(array $data, Booking $booking): array
    {
        $booking->loadMissing('opportunity');

        if (Schema::hasColumn('jobs', 'opportunity_id')) {
            $data['opportunity_id'] = $booking->opportunity_id;
        }

        if (Schema::hasColumn('jobs', 'lead_id')) {
            $data['lead_id'] = $booking->lead_id ?: $booking->opportunity?->lead_id;
        }

        return $data;
    }

    protected function applyJourneyLinksToInvoice(Invoice $invoice, Job $job): void
    {
        if (Schema::hasColumn('invoices', 'booking_id')) {
            $invoice->booking_id = $job->booking_id;
        }

        if (Schema::hasColumn('invoices', 'opportunity_id')) {
            $invoice->opportunity_id = $job->opportunity_id;
        }

        if (Schema::hasColumn('invoices', 'lead_id')) {
            $invoice->lead_id = $job->lead_id;
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

        $invoice = new Invoice([
            'company_id' => $companyId,
            'client_id' => $job->client_id,
            'job_id' => $job->id,
            'amount' => 0,
            'status' => 'pending',
            'due_date' => now()->addDays(7),
            'currency' => 'AED',
            'source' => 'generated',
        ]);

        $this->applyJourneyLinksToInvoice($invoice, $job);

        $invoice->save();
    }

    protected function createOrUpdateCompletionInvoice(
        Job $job,
        ?string $invoiceNumber,
        mixed $invoiceAmount,
        string $status = 'paid'
    ): Invoice {
        $companyId = (int) $job->company_id;

        $invoice = $job->invoice()
            ->where('company_id', $companyId)
            ->first() ?: new Invoice();

        $invoice->company_id = $job->company_id;
        $invoice->client_id = $job->client_id;
        $invoice->job_id = $job->id;
        $this->applyJourneyLinksToInvoice($invoice, $job);
        $invoice->amount = $invoiceAmount ?? $invoice->amount ?? 0;
        $invoice->status = $status;
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

        return $invoice;
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
