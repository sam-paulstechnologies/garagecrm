<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
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

    public function index(Request $request)
    {
        abort_unless(Schema::hasTable('invoices'), 404, 'Invoices table not found.');

        $companyId = $this->companyId();

        $q = trim((string) $request->get('q', ''));
        $status = trim((string) $request->get('status', ''));
        $paymentStatus = trim((string) $request->get('payment_status', ''));

        $query = DB::table('invoices');

        if (Schema::hasColumn('invoices', 'company_id')) {
            $query->where('company_id', $companyId);
        }

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

                if (Schema::hasColumn('invoices', 'job_id') && is_numeric($q)) {
                    $sub->orWhere('job_id', (int) $q);
                }

                if (Schema::hasColumn('invoices', 'booking_id') && is_numeric($q)) {
                    $sub->orWhere('booking_id', (int) $q);
                }

                if (Schema::hasColumn('invoices', 'client_id') && is_numeric($q)) {
                    $sub->orWhere('client_id', (int) $q);
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

        $invoiceIds = collect($invoices->items())->pluck('id')->filter()->values();

        $jobIds = collect($invoices->items())->pluck('job_id')->filter()->unique()->values();
        $bookingIds = collect($invoices->items())->pluck('booking_id')->filter()->unique()->values();
        $clientIds = collect($invoices->items())->pluck('client_id')->filter()->unique()->values();

        $jobs = $this->getJobs($jobIds);
        $bookings = $this->getBookings($bookingIds);
        $clients = $this->getClients($clientIds);

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
        abort_unless(Schema::hasTable('invoices'), 404, 'Invoices table not found.');

        $companyId = $this->companyId();

        $query = DB::table('invoices')->where('id', $invoice);

        if (Schema::hasColumn('invoices', 'company_id')) {
            $query->where('company_id', $companyId);
        }

        $invoiceRow = $query->first();

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
        abort_unless(Schema::hasTable('invoices'), 404, 'Invoices table not found.');

        $companyId = $this->companyId();

        $query = DB::table('invoices')->where('id', $invoice);

        if (Schema::hasColumn('invoices', 'company_id')) {
            $query->where('company_id', $companyId);
        }

        $invoiceRow = $query->first();

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
                ->update($payload);
        }

        return back()->with('success', 'Invoice marked as paid.');
    }

    public function markUnpaid(int $invoice)
    {
        abort_unless(Schema::hasTable('invoices'), 404, 'Invoices table not found.');

        $companyId = $this->companyId();

        $query = DB::table('invoices')->where('id', $invoice);

        if (Schema::hasColumn('invoices', 'company_id')) {
            $query->where('company_id', $companyId);
        }

        $invoiceRow = $query->first();

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
                ->update($payload);
        }

        return back()->with('success', 'Invoice marked as unpaid.');
    }

    protected function invoiceCounts(int $companyId): array
    {
        $base = DB::table('invoices');

        if (Schema::hasColumn('invoices', 'company_id')) {
            $base->where('company_id', $companyId);
        }

        $total = (clone $base)->count();

        $issued = 0;
        $paid = 0;
        $unpaid = 0;
        $totalAmount = 0;

        if (Schema::hasColumn('invoices', 'status')) {
            $issued = (clone $base)->whereIn('status', ['issued', 'open', 'unpaid'])->count();
        }

        if (Schema::hasColumn('invoices', 'payment_status')) {
            $paid = (clone $base)->where('payment_status', 'paid')->count();
            $unpaid = (clone $base)->where('payment_status', 'unpaid')->count();
        } elseif (Schema::hasColumn('invoices', 'status')) {
            $paid = (clone $base)->where('status', 'paid')->count();
            $unpaid = (clone $base)->whereIn('status', ['issued', 'open', 'unpaid'])->count();
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

    protected function getJobs($ids)
    {
        if (! Schema::hasTable('jobs') || $ids->isEmpty()) {
            return collect();
        }

        return DB::table('jobs')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    protected function getBookings($ids)
    {
        if (! Schema::hasTable('bookings') || $ids->isEmpty()) {
            return collect();
        }

        return DB::table('bookings')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    protected function getClients($ids)
    {
        if (! Schema::hasTable('clients') || $ids->isEmpty()) {
            return collect();
        }

        return DB::table('clients')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');
    }

    protected function getJob(int $id, int $companyId)
    {
        if (! Schema::hasTable('jobs')) {
            return null;
        }

        $query = DB::table('jobs')->where('id', $id);

        if (Schema::hasColumn('jobs', 'company_id')) {
            $query->where('company_id', $companyId);
        }

        return $query->first();
    }

    protected function getBooking(int $id, int $companyId)
    {
        if (! Schema::hasTable('bookings')) {
            return null;
        }

        $query = DB::table('bookings')->where('id', $id);

        if (Schema::hasColumn('bookings', 'company_id')) {
            $query->where('company_id', $companyId);
        }

        return $query->first();
    }

    protected function getClient(int $id, int $companyId)
    {
        if (! Schema::hasTable('clients')) {
            return null;
        }

        $query = DB::table('clients')->where('id', $id);

        if (Schema::hasColumn('clients', 'company_id')) {
            $query->where('company_id', $companyId);
        }

        return $query->first();
    }
}