<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InvoiceController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    protected function requireTenantColumn(string $table): void
    {
        abort_unless(Schema::hasTable($table), 404, ucfirst($table) . ' table not found.');

        abort_unless(
            Schema::hasColumn($table, 'company_id'),
            500,
            "Security configuration error: {$table}.company_id is required."
        );
    }

    public function index(Request $request)
    {
        $this->requireTenantColumn('invoices');

        $companyId = $this->companyId();

        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $paymentStatus = trim((string) $request->get('payment_status', ''));

        $query = DB::table('invoices')
            ->where('company_id', $companyId);

        if ($status !== '' && Schema::hasColumn('invoices', 'status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus !== '' && Schema::hasColumn('invoices', 'payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                foreach ([
                    'invoice_number',
                    'reference_number',
                    'status',
                    'payment_status',
                    'notes',
                    'invoice_notes',
                ] as $column) {
                    if (Schema::hasColumn('invoices', $column)) {
                        $sub->orWhere($column, 'like', '%' . $q . '%');
                    }
                }

                if (is_numeric($q)) {
                    if (Schema::hasColumn('invoices', 'job_id')) {
                        $sub->orWhere('job_id', (int) $q);
                    }

                    if (Schema::hasColumn('invoices', 'booking_id')) {
                        $sub->orWhere('booking_id', (int) $q);
                    }

                    if (Schema::hasColumn('invoices', 'client_id')) {
                        $sub->orWhere('client_id', (int) $q);
                    }
                }
            });
        }

        $orderColumn = Schema::hasColumn('invoices', 'issued_at')
            ? 'issued_at'
            : (Schema::hasColumn('invoices', 'invoice_date') ? 'invoice_date' : 'id');

        $invoices = $query
            ->orderByDesc($orderColumn)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $invoiceItems = collect($invoices->items());

        $jobIds = $invoiceItems->pluck('job_id')->filter()->unique()->values();
        $bookingIds = $invoiceItems->pluck('booking_id')->filter()->unique()->values();
        $clientIds = $invoiceItems->pluck('client_id')->filter()->unique()->values();

        $jobs = $this->getJobs($jobIds, $companyId);
        $bookings = $this->getBookings($bookingIds, $companyId);
        $clients = $this->getClients($clientIds, $companyId);

        $counts = $this->invoiceCounts($companyId);

        return view('manager.invoices.index', [
            'invoices' => $invoices,
            'jobs' => $jobs,
            'bookings' => $bookings,
            'clients' => $clients,
            'counts' => $counts,
            'q' => $q,
            'status' => $status,
            'paymentStatus' => $paymentStatus,
        ]);
    }

    public function show(int $invoice)
    {
        $this->requireTenantColumn('invoices');

        $companyId = $this->companyId();

        $invoiceRow = DB::table('invoices')
            ->where('id', $invoice)
            ->where('company_id', $companyId)
            ->first();

        abort_if(! $invoiceRow, 404);

        $job = null;
        $booking = null;
        $client = null;

        if (! empty($invoiceRow->job_id)) {
            $job = $this->getJob((int) $invoiceRow->job_id, $companyId);
        }

        if (! empty($invoiceRow->booking_id)) {
            $booking = $this->getBooking((int) $invoiceRow->booking_id, $companyId);
        }

        if (! empty($invoiceRow->client_id)) {
            $client = $this->getClient((int) $invoiceRow->client_id, $companyId);
        }

        if (! $client && $job && ! empty($job->client_id)) {
            $client = $this->getClient((int) $job->client_id, $companyId);
        }

        if (! $client && $booking && ! empty($booking->client_id)) {
            $client = $this->getClient((int) $booking->client_id, $companyId);
        }

        return view('manager.invoices.show', [
            'invoice' => $invoiceRow,
            'job' => $job,
            'booking' => $booking,
            'client' => $client,
        ]);
    }

    public function markPaid(int $invoice)
    {
        $this->requireTenantColumn('invoices');

        $companyId = $this->companyId();

        $invoiceRow = DB::table('invoices')
            ->where('id', $invoice)
            ->where('company_id', $companyId)
            ->first();

        abort_if(! $invoiceRow, 404);

        $payload = [];

        if (Schema::hasColumn('invoices', 'payment_status')) {
            $payload['payment_status'] = 'paid';
        }

        if (Schema::hasColumn('invoices', 'status')) {
            $payload['status'] = 'paid';
        }

        if (Schema::hasColumn('invoices', 'paid_at')) {
            $payload['paid_at'] = now();
        }

        if (Schema::hasColumn('invoices', 'updated_by')) {
            $payload['updated_by'] = auth()->id();
        }

        if (Schema::hasColumn('invoices', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        if (! empty($payload)) {
            DB::table('invoices')
                ->where('id', $invoiceRow->id)
                ->where('company_id', $companyId)
                ->update($payload);
        }

        return back()->with('success', 'Invoice marked as paid.');
    }

    public function markUnpaid(int $invoice)
    {
        $this->requireTenantColumn('invoices');

        $companyId = $this->companyId();

        $invoiceRow = DB::table('invoices')
            ->where('id', $invoice)
            ->where('company_id', $companyId)
            ->first();

        abort_if(! $invoiceRow, 404);

        $payload = [];

        if (Schema::hasColumn('invoices', 'payment_status')) {
            $payload['payment_status'] = 'unpaid';
        }

        if (Schema::hasColumn('invoices', 'status')) {
            $payload['status'] = 'issued';
        }

        if (Schema::hasColumn('invoices', 'paid_at')) {
            $payload['paid_at'] = null;
        }

        if (Schema::hasColumn('invoices', 'updated_by')) {
            $payload['updated_by'] = auth()->id();
        }

        if (Schema::hasColumn('invoices', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        if (! empty($payload)) {
            DB::table('invoices')
                ->where('id', $invoiceRow->id)
                ->where('company_id', $companyId)
                ->update($payload);
        }

        return back()->with('success', 'Invoice marked as unpaid.');
    }

    protected function invoiceCounts(int $companyId): array
    {
        $this->requireTenantColumn('invoices');

        $base = DB::table('invoices')
            ->where('company_id', $companyId);

        $total = (clone $base)->count();

        $issued = 0;
        $paid = 0;
        $unpaid = 0;
        $totalAmount = 0;

        if (Schema::hasColumn('invoices', 'status')) {
            $issued = (clone $base)
                ->whereIn('status', ['issued', 'open', 'unpaid'])
                ->count();
        }

        if (Schema::hasColumn('invoices', 'payment_status')) {
            $paid = (clone $base)->where('payment_status', 'paid')->count();
            $unpaid = (clone $base)->where('payment_status', 'unpaid')->count();
        } elseif (Schema::hasColumn('invoices', 'status')) {
            $paid = (clone $base)->where('status', 'paid')->count();
            $unpaid = (clone $base)
                ->whereIn('status', ['issued', 'open', 'unpaid'])
                ->count();
        }

        foreach (['total_amount', 'grand_total', 'amount'] as $amountColumn) {
            if (Schema::hasColumn('invoices', $amountColumn)) {
                $totalAmount = (float) (clone $base)->sum($amountColumn);
                break;
            }
        }

        return [
            'total' => $total,
            'issued' => $issued,
            'paid' => $paid,
            'unpaid' => $unpaid,
            'total_amount' => $totalAmount,
        ];
    }

    protected function getJobs(Collection $ids, int $companyId): Collection
    {
        if (! Schema::hasTable('jobs') || $ids->isEmpty()) {
            return collect();
        }

        if (! Schema::hasColumn('jobs', 'company_id')) {
            return collect();
        }

        return DB::table('jobs')
            ->where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    protected function getBookings(Collection $ids, int $companyId): Collection
    {
        if (! Schema::hasTable('bookings') || $ids->isEmpty()) {
            return collect();
        }

        if (! Schema::hasColumn('bookings', 'company_id')) {
            return collect();
        }

        return DB::table('bookings')
            ->where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    protected function getClients(Collection $ids, int $companyId): Collection
    {
        if (! Schema::hasTable('clients') || $ids->isEmpty()) {
            return collect();
        }

        if (! Schema::hasColumn('clients', 'company_id')) {
            return collect();
        }

        return DB::table('clients')
            ->where('company_id', $companyId)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    protected function getJob(int $id, int $companyId)
    {
        if (! Schema::hasTable('jobs')) {
            return null;
        }

        if (! Schema::hasColumn('jobs', 'company_id')) {
            return null;
        }

        return DB::table('jobs')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();
    }

    protected function getBooking(int $id, int $companyId)
    {
        if (! Schema::hasTable('bookings')) {
            return null;
        }

        if (! Schema::hasColumn('bookings', 'company_id')) {
            return null;
        }

        return DB::table('bookings')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();
    }

    protected function getClient(int $id, int $companyId)
    {
        if (! Schema::hasTable('clients')) {
            return null;
        }

        if (! Schema::hasColumn('clients', 'company_id')) {
            return null;
        }

        return DB::table('clients')
            ->where('id', $id)
            ->where('company_id', $companyId)
            ->first();
    }
}