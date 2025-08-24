<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job\Invoice;
use App\Models\Client\Client;
use App\Models\Job\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    /* ---------- Guards ---------- */
    protected function guardCompanyOrAbort($companyId)
    {
        abort_if($companyId !== auth()->user()->company_id, 403);
    }

    protected function invoicesScope()
    {
        return Invoice::where('company_id', auth()->user()->company_id);
    }

    /* ---------- CRUD ---------- */
    public function index()
    {
        $invoices = $this->invoicesScope()
            ->with(['client','job'])
            ->latest('id')
            ->paginate(20);

        return view('admin.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;
        $clients   = Client::where('company_id', $companyId)->orderBy('name')->get();

        // Jobs will be loaded via AJAX after client selection
        $jobs = collect();

        return view('admin.invoices.create', compact('clients','jobs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'    => ['required','exists:clients,id'],
            'job_id'       => ['nullable','exists:jobs,id'], // OPTIONAL
            'amount'       => ['required','numeric','min:0'],
            'status'       => ['required','in:pending,paid,overdue'],
            'due_date'     => ['nullable','date'],
            'invoice_file' => ['nullable','file','mimes:pdf,jpg,jpeg,png,webp','max:5120'],
        ]);

        $data['company_id'] = auth()->user()->company_id;

        if (!empty($data['job_id'])) {
            $job = Job::findOrFail($data['job_id']);
            $this->guardCompanyOrAbort($job->company_id);
            // optional: $data['client_id'] = $job->client_id;
        }

        if ($request->hasFile('invoice_file')) {
            $path = $request->file('invoice_file')->store('invoices', 'public');
            $data['file_path'] = $path;
            $data['file_type'] = $request->file('invoice_file')->getClientMimeType();
        }

        $invoice = Invoice::create($data);

        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);
        return view('admin.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $companyId = auth()->user()->company_id;
        $clients   = Client::where('company_id', $companyId)->orderBy('name')->get();

        // Preload jobs for the invoice’s client
        $jobs = Job::where('company_id', $companyId)
            ->where('client_id', $invoice->client_id)
            ->latest('id')
            ->get();

        return view('admin.invoices.edit', compact('invoice','clients','jobs'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $data = $request->validate([
            'client_id'    => ['required','exists:clients,id'],
            'job_id'       => ['nullable','exists:jobs,id'], // OPTIONAL
            'amount'       => ['required','numeric','min:0'],
            'status'       => ['required','in:pending,paid,overdue'],
            'due_date'     => ['nullable','date'],
            'invoice_file' => ['nullable','file','mimes:pdf,jpg,jpeg,png,webp','max:5120'],
        ]);

        if (!empty($data['job_id'])) {
            $job = Job::findOrFail($data['job_id']);
            $this->guardCompanyOrAbort($job->company_id);
            // optional: $data['client_id'] = $job->client_id;
        }

        if ($request->hasFile('invoice_file')) {
            if ($invoice->file_path && Storage::disk('public')->exists($invoice->file_path)) {
                Storage::disk('public')->delete($invoice->file_path);
            }
            $path = $request->file('invoice_file')->store('invoices', 'public');
            $data['file_path'] = $path;
            $data['file_type'] = $request->file('invoice_file')->getClientMimeType();
        }

        $invoice->update($data);

        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);
        $invoice->delete();

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice deleted.');
    }

    /* ---------- Files: Download & View ---------- */

    public function download(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $path = $invoice->file_path;
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        // Build friendly filename like invoice-INV-0001.pdf
        $ext      = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';
        $number   = $invoice->number ?? $invoice->invoice_no ?? $invoice->code ?? $invoice->id;
        $filename = "invoice-{$number}.{$ext}";

        $mime = $invoice->file_type
            ?? (Storage::disk('public')->mimeType($path) ?: 'application/octet-stream');

        return Storage::disk('public')->download($path, $filename, ['Content-Type' => $mime]);
    }

    public function view(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $path = $invoice->file_path;
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        $mime = $invoice->file_type
            ?? (Storage::disk('public')->mimeType($path) ?: 'application/pdf');

        // Only stream inline if it's a PDF; otherwise force download
        if (stripos($mime, 'pdf') === false) {
            return Storage::disk('public')->download($path);
        }

        return response()->file(Storage::disk('public')->path($path), ['Content-Type' => $mime]);
    }

    /* ---------- AJAX ---------- */
    public function jobsByClient(Client $client)
    {
        $this->guardCompanyOrAbort($client->company_id);

        $jobs = Job::query()
            ->where('company_id', $client->company_id)
            ->where('client_id', $client->id)
            ->latest('id')
            ->get(['id','job_code','status','start_time','end_time']);

        return response()->json($jobs);
    }
}
