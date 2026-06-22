<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Job\Job;
use App\Services\Jobs\JobActionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class JobController extends Controller
{
    public function __construct(
        protected JobActionService $jobActionService
    ) {}

    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    public function index(Request $request)
    {
        $companyId = $this->companyId();

        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $validStatuses = ['pending', 'in_progress', 'completed'];

        if ($status !== '' && ! in_array($status, $validStatuses, true)) {
            $status = '';
        }

        $jobs = Job::with([
                'client',
                'booking',
                'assignedUser',
            ])
            ->where('company_id', $companyId)
            ->where('is_archived', false)
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('job_code', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('work_summary', 'like', "%{$q}%")
                        ->orWhere('status', 'like', "%{$q}%")
                        ->orWhereHas('client', function ($clientQuery) use ($q) {
                            $clientQuery->where('name', 'like', "%{$q}%")
                                ->orWhere('phone', 'like', "%{$q}%")
                                ->orWhere('email', 'like', "%{$q}%")
                                ->orWhere('whatsapp', 'like', "%{$q}%");
                        });
                });
            })
            ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'in_progress' THEN 2 WHEN 'completed' THEN 3 ELSE 4 END")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'pending' => $this->countByStatus($companyId, 'pending'),
            'in_progress' => $this->countByStatus($companyId, 'in_progress'),
            'completed' => $this->countByStatus($companyId, 'completed'),
        ];

        return view('manager.jobs.index', [
            'jobs' => $jobs,
            'q' => $q,
            'status' => $status,
            'counts' => $counts,
        ]);
    }

    public function completed(Request $request)
    {
        $request->merge(['status' => 'completed']);

        return $this->index($request);
    }

    public function show(Job $job)
    {
        $this->authorizeJob($job);

        $job->load([
            'client',
            'booking.client',
            'booking.vehicleData.make',
            'booking.vehicleData.model',
            'assignedUser',
        ]);

        $teamMembers = $this->teamMembers($job->company_id);
        $invoice = $this->findInvoiceForJob($job);

        return view('manager.jobs.show', [
            'job' => $job,
            'teamMembers' => $teamMembers,
            'invoice' => $invoice,
        ]);
    }

    public function updateStatus(Request $request, Job $job)
    {
        $this->authorizeJob($job);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:pending,in_progress'],
        ]);

        try {
            $this->jobActionService->updateStatus(
                $job,
                $data['status'],
                (int) auth()->id()
            );

            return back()->with('success', 'Job status updated successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'job' => $e->getMessage() ?: 'Unable to update job status.',
            ]);
        }
    }

    public function completeWithInvoice(Request $request, Job $job)
    {
        $this->authorizeJob($job);

        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:100'],
            'invoice_amount' => ['required', 'numeric', 'min:1'],
            'labour_amount' => ['nullable', 'numeric', 'min:0'],
            'parts_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'invoice_notes' => ['nullable', 'string', 'max:2000'],
            'work_summary' => ['nullable', 'string', 'max:4000'],
            'issues_found' => ['nullable', 'string', 'max:4000'],
            'parts_used' => ['nullable', 'string', 'max:4000'],
            'vehicle_mileage' => ['nullable', 'integer', 'min:0'],
            'total_time_minutes' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            DB::transaction(function () use ($job, $data) {
                $this->jobActionService->updateWorkDetails(
                    $job,
                    [
                        'work_summary' => $data['work_summary'] ?? $job->work_summary,
                        'issues_found' => $data['issues_found'] ?? $job->issues_found,
                        'parts_used' => $data['parts_used'] ?? $job->parts_used,
                        'vehicle_mileage' => $data['vehicle_mileage'] ?? $job->vehicle_mileage,
                        'total_time_minutes' => $data['total_time_minutes'] ?? $job->total_time_minutes,
                    ],
                    (int) auth()->id()
                );

                if (Schema::hasTable('invoices')) {
                    $this->createOrUpdateInvoice($job->fresh(['client', 'booking']), $data);
                }

                $this->jobActionService->updateStatus(
                    $job->fresh(),
                    'completed',
                    (int) auth()->id()
                );
            });

            return back()->with('success', Schema::hasTable('invoices')
                ? 'Job completed and invoice captured successfully.'
                : 'Job completed successfully. Invoice table was not found, so invoice was not created.'
            );
        } catch (\Throwable $e) {
            return back()->withErrors([
                'job' => $e->getMessage() ?: 'Unable to complete job and capture invoice.',
            ]);
        }
    }

    public function assign(Request $request, Job $job)
    {
        $this->authorizeJob($job);

        $data = $request->validate([
            'assigned_to' => ['nullable', 'integer'],
        ]);

        try {
            $this->jobActionService->assign(
                $job,
                $data['assigned_to'] ?? null,
                (int) auth()->id()
            );

            return back()->with('success', 'Job assignment updated successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'job' => $e->getMessage() ?: 'Unable to assign job.',
            ]);
        }
    }

    public function updateWorkDetails(Request $request, Job $job)
    {
        $this->authorizeJob($job);

        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:2000'],
            'work_summary' => ['nullable', 'string', 'max:4000'],
            'issues_found' => ['nullable', 'string', 'max:4000'],
            'parts_used' => ['nullable', 'string', 'max:4000'],
            'vehicle_mileage' => ['nullable', 'integer', 'min:0'],
            'total_time_minutes' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $this->jobActionService->updateWorkDetails(
                $job,
                $data,
                (int) auth()->id()
            );

            return back()->with('success', 'Job details updated successfully.');
        } catch (\Throwable $e) {
            return back()->withErrors([
                'job' => $e->getMessage() ?: 'Unable to update job details.',
            ]);
        }
    }

    protected function authorizeJob(Job $job): void
    {
        abort_if((int) $job->company_id !== $this->companyId(), 403);
    }

    protected function countByStatus(int $companyId, string $status): int
    {
        return (int) Job::where('company_id', $companyId)
            ->where('is_archived', false)
            ->where('status', $status)
            ->count();
    }

    protected function teamMembers(int $companyId)
    {
        if (! Schema::hasTable('users')) {
            return collect();
        }

        return DB::table('users')
            ->where('company_id', $companyId)
            ->whereIn('role', [
                'manager',
                'mechanic',
                'technician',
                'supervisor',
                'admin',
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }

    protected function createOrUpdateInvoice(Job $job, array $data): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        $labourAmount = (float) ($data['labour_amount'] ?? 0);
        $partsAmount = (float) ($data['parts_amount'] ?? 0);
        $discountAmount = (float) ($data['discount_amount'] ?? 0);
        $vatRate = (float) ($data['vat_rate'] ?? 5);

        $explicitInvoiceAmount = (float) ($data['invoice_amount'] ?? 0);
        $subTotal = max(0, $explicitInvoiceAmount > 0 ? $explicitInvoiceAmount : ($labourAmount + $partsAmount));
        $taxableAmount = max(0, $subTotal - $discountAmount);
        $vatAmount = round($taxableAmount * ($vatRate / 100), 2);
        $totalAmount = $explicitInvoiceAmount > 0
            ? round($explicitInvoiceAmount, 2)
            : round($taxableAmount + $vatAmount, 2);

        $existingInvoice = $this->findInvoiceForJob($job);

        $payload = [];

        $this->setIfColumnExists($payload, 'company_id', $job->company_id);
        $this->setIfColumnExists($payload, 'job_id', $job->id);
        $this->setIfColumnExists($payload, 'booking_id', $job->booking_id ?? null);
        $this->setIfColumnExists($payload, 'client_id', $job->client_id ?? $job->booking?->client_id ?? null);

        $invoiceNumber = $data['invoice_number']
            ?? $existingInvoice->number
            ?? $existingInvoice->invoice_number
            ?? $existingInvoice->reference_number
            ?? $this->nextInvoiceNumber($job->company_id);

        $this->setIfColumnExists($payload, 'number', $invoiceNumber);
        $this->setIfColumnExists($payload, 'invoice_number', $invoiceNumber);
        $this->setIfColumnExists($payload, 'reference_number', $invoiceNumber);

        $this->setIfColumnExists($payload, 'labour_amount', $labourAmount);
        $this->setIfColumnExists($payload, 'labor_amount', $labourAmount);
        $this->setIfColumnExists($payload, 'parts_amount', $partsAmount);
        $this->setIfColumnExists($payload, 'subtotal', $subTotal);
        $this->setIfColumnExists($payload, 'sub_total', $subTotal);
        $this->setIfColumnExists($payload, 'discount_amount', $discountAmount);
        $this->setIfColumnExists($payload, 'vat_rate', $vatRate);
        $this->setIfColumnExists($payload, 'tax_rate', $vatRate);
        $this->setIfColumnExists($payload, 'vat_amount', $vatAmount);
        $this->setIfColumnExists($payload, 'tax_amount', $vatAmount);
        $this->setIfColumnExists($payload, 'total_amount', $totalAmount);
        $this->setIfColumnExists($payload, 'grand_total', $totalAmount);
        $this->setIfColumnExists($payload, 'amount', $totalAmount);

        /*
        |--------------------------------------------------------------------------
        | Invoice schema alignment
        |--------------------------------------------------------------------------
        | Current SQL source of truth:
        | - invoices.status accepts pending, paid, overdue.
        | - invoices.due_date is NOT NULL.
        |
        | Do not write legacy values like issued / unpaid into status.
        */
        $this->setIfColumnExists($payload, 'status', 'pending');
        $this->setIfColumnExists($payload, 'payment_status', 'pending');
        $this->setIfColumnExists($payload, 'source', 'manager_job_completion');
        $this->setIfColumnExists($payload, 'currency', 'AED');
        $this->setIfColumnExists($payload, 'notes', $data['invoice_notes'] ?? null);
        $this->setIfColumnExists($payload, 'invoice_notes', $data['invoice_notes'] ?? null);
        $this->setIfColumnExists($payload, 'issued_at', now());
        $this->setIfColumnExists($payload, 'invoice_date', now()->toDateString());
        $this->setIfColumnExists($payload, 'due_date', now()->addDays(7)->toDateString());
        $this->setIfColumnExists($payload, 'created_by', auth()->id());
        $this->setIfColumnExists($payload, 'updated_by', auth()->id());

        $payload['updated_at'] = now();

        if ($existingInvoice) {
            DB::table('invoices')
                ->where('id', $existingInvoice->id)
                ->update($payload);

            return;
        }

        if (Schema::hasColumn('invoices', 'created_at')) {
            $payload['created_at'] = now();
        }

        $invoiceId = DB::table('invoices')->insertGetId($payload);

        if (Schema::hasColumn('jobs', 'invoice_id')) {
            DB::table('jobs')
                ->where('id', $job->id)
                ->update([
                    'invoice_id' => $invoiceId,
                    'updated_at' => now(),
                ]);
        }
    }

    protected function findInvoiceForJob(Job $job)
    {
        if (! Schema::hasTable('invoices')) {
            return null;
        }

        $query = DB::table('invoices');

        if (Schema::hasColumn('invoices', 'company_id')) {
            $query->where('company_id', $job->company_id);
        }

        $query->where(function ($sub) use ($job) {
            if (Schema::hasColumn('invoices', 'job_id')) {
                $sub->orWhere('job_id', $job->id);
            }

            if (Schema::hasColumn('jobs', 'invoice_id') && ! empty($job->invoice_id)) {
                $sub->orWhere('id', $job->invoice_id);
            }

            if (Schema::hasColumn('invoices', 'booking_id') && ! empty($job->booking_id)) {
                $sub->orWhere('booking_id', $job->booking_id);
            }
        });

        return $query->latest('id')->first();
    }

    protected function nextInvoiceNumber(int $companyId): string
    {
        $nextId = 1;

        if (Schema::hasTable('invoices')) {
            $query = DB::table('invoices');

            if (Schema::hasColumn('invoices', 'company_id')) {
                $query->where('company_id', $companyId);
            }

            $nextId = ((int) $query->max('id')) + 1;
        }

        return 'INV-' . now()->format('Ymd') . '-' . str_pad((string) $nextId, 5, '0', STR_PAD_LEFT);
    }

    protected function setIfColumnExists(array &$payload, string $column, mixed $value): void
    {
        if (Schema::hasColumn('invoices', $column)) {
            $payload[$column] = $value;
        }
    }
}
