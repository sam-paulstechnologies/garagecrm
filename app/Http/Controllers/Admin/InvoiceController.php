<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Job\Invoice;
use App\Models\Job\Job;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Guards
    |--------------------------------------------------------------------------
    */

    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    protected function guardCompanyOrAbort($companyId): void
    {
        abort_unless((int) $companyId === $this->companyId(), 404);
    }

    protected function invoicesScope()
    {
        return Invoice::query()->where('company_id', $this->companyId());
    }

    protected function invoiceHasColumn(string $column): bool
    {
        return Schema::hasColumn('invoices', $column);
    }

    /*
    |--------------------------------------------------------------------------
    | Invoice List
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $companyId = $this->companyId();

        $q = trim((string) $request->get('q', $request->get('search', '')));
        $status = (string) $request->get('status', '');

        $invoiceFilters = [
            'date_range'    => $request->get('date_range', 'all_time'),
            'lead_source'   => $request->get('lead_source', 'all'),
            'assigned_user' => $request->get('assigned_user', 'all'),
            'service_type'  => $request->get('service_type', 'all'),
            'customer_type' => $request->get('customer_type', 'all'),
            'from_date'     => $request->get('from_date'),
            'to_date'       => $request->get('to_date'),
        ];

        $assignedUsers = User::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $query = $this->invoicesScope()
            ->with([
                'client' => fn ($clientQuery) => $clientQuery->where('company_id', $companyId),
                'job' => fn ($jobQuery) => $jobQuery->where('company_id', $companyId),
            ]);

        $this->applyInvoiceIndexFilters($query, $request, $companyId);

        $invoices = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $base = $this->invoicesScope();

        $this->applyInvoiceIndexFilters($base, $request, $companyId, false);

        $stats = [
            'total'       => (clone $base)->count(),
            'paid'        => (clone $base)->where('status', 'paid')->count(),
            'pending'     => (clone $base)->where('status', 'pending')->count(),
            'overdue'     => (clone $base)->where('status', 'overdue')->count(),
            'roi_revenue' => (clone $base)->where('status', 'paid')->sum('amount'),
        ];

        return view('admin.invoices.index', compact(
            'invoices',
            'q',
            'status',
            'stats',
            'invoiceFilters',
            'assignedUsers'
        ));
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
            ->get(['id', 'name', 'phone', 'email']);

        $jobs = Job::query()
            ->where('company_id', $companyId)
            ->whereIn('status', ['pending', 'in_progress', 'completed'])
            ->latest('id')
            ->get(['id', 'client_id', 'job_code', 'status', 'description']);

        return view('admin.invoices.create', compact('clients', 'jobs'));
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
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('company_id', $companyId),
            ],

            'job_id' => [
                'nullable',
                Rule::exists('jobs', 'id')->where('company_id', $companyId),
            ],

            'number' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:1'],
            'status' => ['required', 'in:pending,paid,overdue'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'max:10'],
        ]);

        $this->validateJobBelongsToClient($data, $companyId);

        $invoiceDate = $data['invoice_date'] ?? now()->toDateString();
        $dueDate = $data['due_date'] ?? $invoiceDate;

        $invoice = new Invoice();

        $invoice->company_id = $companyId;
        $invoice->client_id = $data['client_id'];
        $invoice->job_id = $data['job_id'] ?? null;
        $invoice->number = $data['number'];
        $invoice->amount = $data['amount'];
        $invoice->status = $data['status'];
        $invoice->invoice_date = $invoiceDate;
        $invoice->due_date = $dueDate;
        $invoice->currency = $data['currency'] ?? 'AED';
        $invoice->source = 'generated';
        $invoice->uploaded_by = auth()->id();
        $invoice->version = 1;
        $invoice->is_primary = true;

        if (! empty($invoice->job_id)) {
            $linkedJob = Job::query()
                ->where('company_id', $companyId)
                ->findOrFail($invoice->job_id);

            $this->applyJourneyLinksFromJob($invoice, $linkedJob);
        }

        if ($this->invoiceHasColumn('invoice_number')) {
            $invoice->invoice_number = $data['number'];
        }

        if (! empty($invoice->job_id)) {
            $this->invoicesScope()
                ->where('job_id', $invoice->job_id)
                ->update(['is_primary' => false]);
        }

        $invoice->save();

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $companyId = $this->companyId();

        $invoice->load([
            'client' => fn ($clientQuery) => $clientQuery->where('company_id', $companyId),
            'job' => fn ($jobQuery) => $jobQuery->where('company_id', $companyId),
        ]);

        return view('admin.invoices.show', compact('invoice'));
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $companyId = $this->companyId();

        $invoice->load([
            'client' => fn ($clientQuery) => $clientQuery->where('company_id', $companyId),
            'job' => fn ($jobQuery) => $jobQuery->where('company_id', $companyId),
        ]);

        $clients = Client::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['id', 'name', 'phone', 'email']);

        $jobs = Job::query()
            ->where('company_id', $companyId)
            ->where('client_id', $invoice->client_id)
            ->latest('id')
            ->get(['id', 'client_id', 'job_code', 'status', 'description']);

        return view('admin.invoices.edit', compact('invoice', 'clients', 'jobs'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $companyId = $this->companyId();

        $data = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('company_id', $companyId),
            ],

            'job_id' => [
                'nullable',
                Rule::exists('jobs', 'id')->where('company_id', $companyId),
            ],

            'number' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:1'],
            'status' => ['required', 'in:pending,paid,overdue'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'max:10'],
        ]);

        $this->validateJobBelongsToClient($data, $companyId);

        $invoiceDate = $data['invoice_date']
            ?? $invoice->invoice_date
            ?? now()->toDateString();

        $dueDate = $data['due_date']
            ?? $invoiceDate
            ?? now()->toDateString();

        $invoice->client_id = $data['client_id'];
        $invoice->job_id = $data['job_id'] ?? null;
        $invoice->number = $data['number'];
        $invoice->amount = $data['amount'];
        $invoice->status = $data['status'];
        $invoice->invoice_date = $invoiceDate;
        $invoice->due_date = $dueDate;
        $invoice->currency = $data['currency'] ?? ($invoice->currency ?? 'AED');
        $invoice->source = $invoice->source ?: 'generated';
        $invoice->uploaded_by = $invoice->uploaded_by ?? auth()->id();

        if (! empty($invoice->job_id)) {
            $linkedJob = Job::query()
                ->where('company_id', $companyId)
                ->findOrFail($invoice->job_id);

            $this->applyJourneyLinksFromJob($invoice, $linkedJob);
        } else {
            $this->clearJourneyLinks($invoice);
        }

        if ($this->invoiceHasColumn('invoice_number')) {
            $invoice->invoice_number = $data['number'];
        }

        if (! empty($invoice->job_id)) {
            $this->invoicesScope()
                ->where('job_id', $invoice->job_id)
                ->where('id', '!=', $invoice->id)
                ->update(['is_primary' => false]);

            $invoice->is_primary = true;
        } else {
            $invoice->is_primary = false;
        }

        $invoice->save();

        return redirect()
            ->route('admin.invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    public function destroy(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $invoice->delete();

        return redirect()
            ->route('admin.invoices.index')
            ->with('success', 'Invoice deleted.');
    }

    /*
    |--------------------------------------------------------------------------
    | Download / View Existing Uploaded File
    |--------------------------------------------------------------------------
    */

    public function download(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $path = $this->safeInvoiceFilePath($invoice);

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';
        $number = $invoice->number ?? ($this->invoiceHasColumn('invoice_number') ? $invoice->invoice_number : null) ?? $invoice->id;
        $filename = "invoice-{$number}.{$ext}";

        $mime = $invoice->mime
            ?? $invoice->file_type
            ?? (Storage::disk('public')->mimeType($path) ?: 'application/octet-stream');

        return Storage::disk('public')->download($path, $filename, [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function view(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $path = $this->safeInvoiceFilePath($invoice);

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        $mime = $invoice->mime
            ?? $invoice->file_type
            ?? (Storage::disk('public')->mimeType($path) ?: 'application/pdf');

        if (stripos($mime, 'pdf') === false) {
            return Storage::disk('public')->download($path, null, [
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return response()->file(Storage::disk('public')->path($path), [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX: Jobs by Client
    |--------------------------------------------------------------------------
    */

    public function jobsByClient(Client $client)
    {
        $this->guardCompanyOrAbort($client->company_id);

        $jobs = Job::query()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->latest('id')
            ->get(['id', 'job_code', 'status', 'description']);

        return response()->json($jobs);
    }

    /*
    |--------------------------------------------------------------------------
    | Legacy Upload Methods - now saves invoice details only
    |--------------------------------------------------------------------------
    */

    public function uploadForJob(Request $request, Job $job)
    {
        $this->guardCompanyOrAbort($job->company_id);

        $companyId = $this->companyId();

        $data = $request->validate([
            'number' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:1'],
            'status' => ['nullable', 'in:pending,paid,overdue'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'max:10'],
        ]);

        $invoiceDate = $data['invoice_date'] ?? now()->toDateString();
        $dueDate = $data['due_date'] ?? $invoiceDate;

        $invoice = new Invoice();

        $invoice->company_id = $companyId;
        $invoice->client_id = $job->client_id;
        $invoice->job_id = $job->id;
        $invoice->number = $data['number'];
        $invoice->amount = $data['amount'];
        $invoice->status = $data['status'] ?? 'paid';
        $invoice->invoice_date = $invoiceDate;
        $invoice->due_date = $dueDate;
        $invoice->currency = $data['currency'] ?? 'AED';
        $invoice->source = 'generated';
        $invoice->uploaded_by = auth()->id();
        $invoice->version = 1 + (int) $this->invoicesScope()->where('job_id', $job->id)->max('version');
        $invoice->is_primary = true;

        $this->applyJourneyLinksFromJob($invoice, $job);

        if ($this->invoiceHasColumn('invoice_number')) {
            $invoice->invoice_number = $data['number'];
        }

        $this->invoicesScope()
            ->where('job_id', $job->id)
            ->update(['is_primary' => false]);

        $invoice->save();

        return back()->with('success', 'Invoice details saved.');
    }

    public function uploadForClient(Request $request, Client $client)
    {
        $this->guardCompanyOrAbort($client->company_id);

        $companyId = $this->companyId();
        $job = null;

        $data = $request->validate([
            'job_id' => [
                'nullable',
                Rule::exists('jobs', 'id')->where('company_id', $companyId),
            ],

            'number' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:1'],
            'status' => ['nullable', 'in:pending,paid,overdue'],
            'invoice_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'max:10'],
        ]);

        if (! empty($data['job_id'])) {
            $job = Job::query()
                ->where('company_id', $companyId)
                ->findOrFail($data['job_id']);

            abort_unless((int) $job->client_id === (int) $client->id, 422);
        }

        $invoiceDate = $data['invoice_date'] ?? now()->toDateString();
        $dueDate = $data['due_date'] ?? $invoiceDate;

        $invoice = new Invoice();

        $invoice->company_id = $companyId;
        $invoice->client_id = $client->id;
        $invoice->job_id = $data['job_id'] ?? null;
        $invoice->number = $data['number'];
        $invoice->amount = $data['amount'];
        $invoice->status = $data['status'] ?? 'paid';
        $invoice->invoice_date = $invoiceDate;
        $invoice->due_date = $dueDate;
        $invoice->currency = $data['currency'] ?? 'AED';
        $invoice->source = 'generated';
        $invoice->uploaded_by = auth()->id();
        $invoice->version = 1;
        $invoice->is_primary = ! empty($invoice->job_id);

        $this->applyJourneyLinksFromJob($invoice, $job);

        if ($this->invoiceHasColumn('invoice_number')) {
            $invoice->invoice_number = $data['number'];
        }

        if (! empty($invoice->job_id)) {
            $this->invoicesScope()
                ->where('job_id', $invoice->job_id)
                ->update(['is_primary' => false]);
        }

        $invoice->save();

        return back()->with('success', 'Invoice details saved.');
    }

    public function makePrimary(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        if (! $invoice->job_id) {
            return back()->with('warning', 'Cannot mark as primary without a job.');
        }

        $this->invoicesScope()
            ->where('job_id', $invoice->job_id)
            ->update(['is_primary' => false]);

        $invoice->update(['is_primary' => true]);

        return back()->with('success', 'Marked as primary.');
    }

    /*
    |--------------------------------------------------------------------------
    | Index Helpers
    |--------------------------------------------------------------------------
    */

    protected function applyInvoiceIndexFilters(
        Builder $query,
        Request $request,
        int $companyId,
        bool $includeStatus = true
    ): void {
        $q = trim((string) $request->get('q', $request->get('search', '')));
        $status = (string) $request->get('status', '');
        $dateRange = (string) $request->get('date_range', 'all_time');
        $leadSource = (string) $request->get('lead_source', 'all');
        $assignedUser = (string) $request->get('assigned_user', 'all');
        $serviceType = (string) $request->get('service_type', 'all');
        $customerType = (string) $request->get('customer_type', 'all');

        if ($includeStatus && in_array($status, ['pending', 'paid', 'overdue'], true)) {
            $query->where('status', $status);
        }

        if ($q !== '') {
            $query->where(function ($where) use ($q, $companyId) {
                if ($this->invoiceHasColumn('number')) {
                    $where->orWhere('number', 'like', "%{$q}%");
                }

                if ($this->invoiceHasColumn('invoice_number')) {
                    $where->orWhere('invoice_number', 'like', "%{$q}%");
                }

                if ($this->invoiceHasColumn('amount')) {
                    $where->orWhere('amount', 'like', "%{$q}%");
                }

                $where->orWhereHas('client', function ($clientQuery) use ($q, $companyId) {
                    $clientQuery
                        ->where('company_id', $companyId)
                        ->where(function ($clientSearch) use ($q) {
                            $clientSearch
                                ->where('name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%");
                        });
                });

                $where->orWhereHas('job', function ($jobQuery) use ($q, $companyId) {
                    $jobQuery
                        ->where('company_id', $companyId)
                        ->where(function ($jobSearch) use ($q) {
                            $jobSearch
                                ->where('job_code', 'like', "%{$q}%")
                                ->orWhere('description', 'like', "%{$q}%");
                        });
                });
            });
        }

        [$fromDate, $toDate, $applyDateFilter] = $this->resolveInvoiceDateRange($request, $dateRange);

        if ($applyDateFilter) {
            $dateColumn = $this->firstExistingInvoiceColumn(['invoice_date', 'created_at']);

            if ($dateColumn) {
                $query->whereBetween($dateColumn, [$fromDate, $toDate]);
            }
        }

        if ($leadSource !== 'all') {
            $sourceColumn = $this->firstExistingInvoiceColumn(['lead_source', 'source', 'source_type', 'channel']);

            if ($sourceColumn) {
                $query->where($sourceColumn, $leadSource);
            } else {
                $query->whereHas('job', function ($jobQuery) use ($leadSource, $companyId) {
                    $jobQuery->where('company_id', $companyId);

                    foreach (['lead_source', 'source', 'source_type', 'channel'] as $column) {
                        if (Schema::hasColumn('jobs', $column)) {
                            $jobQuery->where($column, $leadSource);
                            return;
                        }
                    }
                });
            }
        }

        if ($assignedUser !== 'all') {
            $assignedColumn = $this->firstExistingInvoiceColumn(['uploaded_by', 'assigned_to', 'assigned_user_id', 'user_id', 'owner_id']);

            if ($assignedColumn) {
                $query->where($assignedColumn, $assignedUser);
            } else {
                $query->whereHas('job', function ($jobQuery) use ($assignedUser, $companyId) {
                    $jobQuery->where('company_id', $companyId);

                    foreach (['assigned_to', 'assigned_user_id', 'user_id', 'owner_id'] as $column) {
                        if (Schema::hasColumn('jobs', $column)) {
                            $jobQuery->where($column, $assignedUser);
                            return;
                        }
                    }
                });
            }
        }

        if ($serviceType !== 'all') {
            $query->whereHas('job', function ($jobQuery) use ($serviceType, $companyId) {
                $jobQuery->where('company_id', $companyId);
                $this->applyJobServiceTextFilter($jobQuery, $serviceType);
            });
        }

        if ($customerType !== 'all' && $this->invoiceHasColumn('client_id')) {
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
                        foreach (['customer_type', 'type', 'source'] as $column) {
                            if (Schema::hasColumn('clients', $column)) {
                                $clientQuery->orWhere($column, $customerType);
                            }
                        }
                    });
                }
            });
        }
    }

    protected function resolveInvoiceDateRange(Request $request, string $range): array
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

    protected function firstExistingInvoiceColumn(array $columns): ?string
    {
        foreach ($columns as $column) {
            if ($this->invoiceHasColumn($column)) {
                return $column;
            }
        }

        return null;
    }

    protected function applyJobServiceTextFilter(Builder $query, string $serviceType): void
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

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function validateJobBelongsToClient(array $data, int $companyId): void
    {
        if (empty($data['job_id'])) {
            return;
        }

        $job = Job::query()
            ->where('company_id', $companyId)
            ->findOrFail($data['job_id']);

        abort_unless((int) $job->client_id === (int) $data['client_id'], 422);
    }

    protected function applyJourneyLinksFromJob(Invoice $invoice, ?Job $job): void
    {
        if (! $job) {
            return;
        }

        if ($this->invoiceHasColumn('booking_id')) {
            $invoice->booking_id = $job->booking_id;
        }

        if ($this->invoiceHasColumn('opportunity_id')) {
            $invoice->opportunity_id = $job->opportunity_id;
        }

        if ($this->invoiceHasColumn('lead_id')) {
            $invoice->lead_id = $job->lead_id;
        }
    }

    protected function clearJourneyLinks(Invoice $invoice): void
    {
        if ($this->invoiceHasColumn('booking_id')) {
            $invoice->booking_id = null;
        }

        if ($this->invoiceHasColumn('opportunity_id')) {
            $invoice->opportunity_id = null;
        }

        if ($this->invoiceHasColumn('lead_id')) {
            $invoice->lead_id = null;
        }
    }

    protected function safeInvoiceFilePath(Invoice $invoice): ?string
    {
        $path = trim((string) $invoice->file_path);

        if ($path === '') {
            return null;
        }

        if (str_contains($path, '..') || str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:[\/\\\\]/', $path)) {
            abort(404);
        }

        return $path;
    }
}
