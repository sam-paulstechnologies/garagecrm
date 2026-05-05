<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Job\Invoice;
use App\Models\Job\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    /* ---------- Guards ---------- */
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(!$companyId, 403);

        return $companyId;
    }

    protected function guardCompanyOrAbort($companyId): void
    {
        abort_if((int) $companyId !== (int) $this->companyId(), 403);
    }

    protected function invoicesScope()
    {
        return Invoice::where('company_id', $this->companyId());
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
        $companyId = $this->companyId();

        $clients = Client::where('company_id', $companyId)->orderBy('name')->get();
        $jobs = collect(); // load via AJAX after client select

        return view('admin.invoices.create', compact('clients','jobs'));
    }

    public function store(Request $request)
    {
        $companyId = $this->companyId();

        $data = $request->validate([
            'client_id'     => ['required', Rule::exists('clients', 'id')->where('company_id', $companyId)],
            'job_id'        => ['nullable', Rule::exists('jobs', 'id')->where('company_id', $companyId)],
            'amount'        => ['required','numeric','min:0'],
            'status'        => ['required','in:pending,paid,overdue'],
            'due_date'      => ['nullable','date'],

            'number'        => ['nullable','string','max:191'],
            'invoice_date'  => ['nullable','date'],
            'currency'      => ['nullable','string','max:10'],
            'is_primary'    => ['nullable','boolean'],

            'invoice_file'  => ['nullable','file','mimes:pdf,jpg,jpeg,png,webp','max:5120'],
        ]);

        $this->validateJobBelongsToClient($data, $companyId);

        $data['company_id'] = $companyId;
        $data['source'] = 'upload';
        $data['currency'] = $data['currency'] ?? 'AED';
        $data['uploaded_by'] = auth()->id();

        if ($request->hasFile('invoice_file')) {
            $file = $request->file('invoice_file');
            $hash = hash_file('sha256', $file->getRealPath());

            $dupe = $this->invoicesScope()
                ->where('client_id', $data['client_id'])
                ->where('hash', $hash)
                ->first();

            if ($dupe) {
                return redirect()
                    ->back()
                    ->withInput()
                    ->with('warning', "Looks like a duplicate of invoice #{$dupe->id}.");
            }

            $path = $file->store("companies/{$companyId}/invoices", 'public');

            $data['file_path'] = $path;
            $data['file_type'] = $file->getClientOriginalExtension();
            $data['mime'] = $file->getClientMimeType();
            $data['size'] = $file->getSize();
            $data['hash'] = $hash;
        }

        $data['version'] = !empty($data['job_id'])
            ? (1 + (int) $this->invoicesScope()->where('job_id', $data['job_id'])->max('version'))
            : 1;

        $setPrimary = (bool) ($data['is_primary'] ?? false);
        if ($setPrimary && !empty($data['job_id'])) {
            $this->invoicesScope()->where('job_id', $data['job_id'])->update(['is_primary' => false]);
            $data['is_primary'] = true;
        } else {
            $data['is_primary'] = false;
        }

        $invoice = Invoice::create($data);

        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $invoice->load(['client','job']);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $companyId = $this->companyId();

        $clients = Client::where('company_id', $companyId)->orderBy('name')->get();

        $jobs = Job::where('company_id', $companyId)
            ->where('client_id', $invoice->client_id)
            ->latest('id')
            ->get(['id','job_code','status','start_time','end_time']);

        return view('admin.invoices.edit', compact('invoice','clients','jobs'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $companyId = $this->companyId();

        $data = $request->validate([
            'client_id'     => ['required', Rule::exists('clients', 'id')->where('company_id', $companyId)],
            'job_id'        => ['nullable', Rule::exists('jobs', 'id')->where('company_id', $companyId)],
            'amount'        => ['required','numeric','min:0'],
            'status'        => ['required','in:pending,paid,overdue'],
            'due_date'      => ['nullable','date'],

            'number'        => ['nullable','string','max:191'],
            'invoice_date'  => ['nullable','date'],
            'currency'      => ['nullable','string','max:10'],
            'is_primary'    => ['nullable','boolean'],

            'invoice_file'  => ['nullable','file','mimes:pdf,jpg,jpeg,png,webp','max:5120'],
        ]);

        $this->validateJobBelongsToClient($data, $companyId);

        if ($request->hasFile('invoice_file')) {
            if ($invoice->file_path && Storage::disk('public')->exists($invoice->file_path)) {
                Storage::disk('public')->delete($invoice->file_path);
            }

            $file = $request->file('invoice_file');
            $path = $file->store("companies/{$companyId}/invoices", 'public');

            $data['file_path'] = $path;
            $data['file_type'] = $file->getClientOriginalExtension();
            $data['mime'] = $file->getClientMimeType();
            $data['size'] = $file->getSize();
            $data['hash'] = hash_file('sha256', $file->getRealPath());
        }

        $setPrimary = (bool) ($data['is_primary'] ?? false);
        if ($setPrimary && !empty($data['job_id'])) {
            $this->invoicesScope()->where('job_id', $data['job_id'])->update(['is_primary' => false]);
            $data['is_primary'] = true;
        } elseif ($setPrimary && empty($data['job_id'])) {
            $data['is_primary'] = false;
        }

        $data['currency'] = $data['currency'] ?? ($invoice->currency ?? 'AED');
        $data['uploaded_by'] = $invoice->uploaded_by ?? auth()->id();

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

        $ext = pathinfo($path, PATHINFO_EXTENSION) ?: 'pdf';
        $number = $invoice->number ?? $invoice->id;
        $filename = "invoice-{$number}.{$ext}";
        $mime = $invoice->mime
            ?? $invoice->file_type
            ?? (Storage::disk('public')->mimeType($path) ?: 'application/octet-stream');

        return Storage::disk('public')->download($path, $filename, ['Content-Type' => $mime]);
    }

    public function view(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        $path = $invoice->file_path;
        abort_unless($path && Storage::disk('public')->exists($path), 404);

        $mime = $invoice->mime
            ?? $invoice->file_type
            ?? (Storage::disk('public')->mimeType($path) ?: 'application/pdf');

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

    /* ---------- Quick-upload endpoints (Job/Client pages) ---------- */
    public function uploadForJob(Request $request, Job $job)
    {
        $this->guardCompanyOrAbort($job->company_id);

        $companyId = $this->companyId();

        $data = $request->validate([
            'invoice_file' => ['required','file','mimes:pdf,jpg,jpeg,png,webp','max:5120'],
            'number'       => ['nullable','string','max:191'],
            'invoice_date' => ['nullable','date'],
            'due_date'     => ['nullable','date'],
            'currency'     => ['nullable','string','max:10'],
            'amount'       => ['nullable','numeric','min:0'],
            'is_primary'   => ['nullable','boolean'],
        ]);

        $file = $request->file('invoice_file');
        $hash = hash_file('sha256', $file->getRealPath());

        $dupe = $this->invoicesScope()
            ->where('client_id', $job->client_id)
            ->where('hash', $hash)
            ->first();

        if ($dupe) {
            return back()->with('warning', "Looks like a duplicate of invoice #{$dupe->id}.");
        }

        $path = $file->store("companies/{$companyId}/invoices", 'public');

        $setPrimary = (bool) ($data['is_primary'] ?? false);
        if ($setPrimary) {
            $this->invoicesScope()->where('job_id', $job->id)->update(['is_primary' => false]);
        }

        $version = 1 + (int) $this->invoicesScope()->where('job_id', $job->id)->max('version');

        Invoice::create([
            'company_id'   => $companyId,
            'client_id'    => $job->client_id,
            'job_id'       => $job->id,
            'source'       => 'upload',
            'status'       => 'pending',
            'is_primary'   => $setPrimary,

            'number'       => $data['number']       ?? null,
            'invoice_date' => $data['invoice_date'] ?? null,
            'due_date'     => $data['due_date']     ?? null,
            'currency'     => $data['currency']     ?? 'AED',
            'amount'       => $data['amount']       ?? null,

            'file_path'    => $path,
            'file_type'    => $file->getClientOriginalExtension(),
            'mime'         => $file->getClientMimeType(),
            'size'         => $file->getSize(),
            'hash'         => $hash,
            'version'      => $version,
            'uploaded_by'  => auth()->id(),
        ]);

        return back()->with('success', 'Invoice uploaded.');
    }

    public function uploadForClient(Request $request, Client $client)
    {
        $this->guardCompanyOrAbort($client->company_id);

        $companyId = $this->companyId();

        $data = $request->validate([
            'invoice_file' => ['required','file','mimes:pdf,jpg,jpeg,png,webp','max:5120'],
            'job_id'       => ['nullable', Rule::exists('jobs', 'id')->where('company_id', $companyId)],
            'number'       => ['nullable','string','max:191'],
            'invoice_date' => ['nullable','date'],
            'due_date'     => ['nullable','date'],
            'currency'     => ['nullable','string','max:10'],
            'amount'       => ['nullable','numeric','min:0'],
            'is_primary'   => ['nullable','boolean'],
        ]);

        if (!empty($data['job_id'])) {
            $job = Job::where('company_id', $companyId)->findOrFail($data['job_id']);
            abort_unless((int) $job->client_id === (int) $client->id, 422);
        }

        $file = $request->file('invoice_file');
        $hash = hash_file('sha256', $file->getRealPath());

        $dupe = $this->invoicesScope()
            ->where('client_id', $client->id)
            ->where('hash', $hash)
            ->first();

        if ($dupe) {
            return back()->with('warning', "Looks like a duplicate of invoice #{$dupe->id}.");
        }

        $path = $file->store("companies/{$companyId}/invoices", 'public');

        $jobId = $data['job_id'] ?? null;
        $setPrimary = (bool) ($data['is_primary'] ?? false);

        if ($setPrimary && $jobId) {
            $this->invoicesScope()->where('job_id', $jobId)->update(['is_primary' => false]);
        }

        $version = $jobId
            ? (1 + (int) $this->invoicesScope()->where('job_id', $jobId)->max('version'))
            : 1;

        Invoice::create([
            'company_id'   => $companyId,
            'client_id'    => $client->id,
            'job_id'       => $jobId,
            'source'       => 'upload',
            'status'       => 'pending',
            'is_primary'   => ($setPrimary && $jobId) ? true : false,

            'number'       => $data['number']       ?? null,
            'invoice_date' => $data['invoice_date'] ?? null,
            'due_date'     => $data['due_date']     ?? null,
            'currency'     => $data['currency']     ?? 'AED',
            'amount'       => $data['amount']       ?? null,

            'file_path'    => $path,
            'file_type'    => $file->getClientOriginalExtension(),
            'mime'         => $file->getClientMimeType(),
            'size'         => $file->getSize(),
            'hash'         => $hash,
            'version'      => $version,
            'uploaded_by'  => auth()->id(),
        ]);

        return back()->with('success', 'Invoice uploaded.');
    }

    public function makePrimary(Invoice $invoice)
    {
        $this->guardCompanyOrAbort($invoice->company_id);

        if (!$invoice->job_id) {
            return back()->with('warning', 'Cannot mark as primary without a Job.');
        }

        $this->invoicesScope()->where('job_id', $invoice->job_id)->update(['is_primary' => false]);
        $invoice->update(['is_primary' => true]);

        return back()->with('success', 'Marked as primary.');
    }

    protected function validateJobBelongsToClient(array $data, int $companyId): void
    {
        if (empty($data['job_id'])) {
            return;
        }

        $job = Job::where('company_id', $companyId)
            ->findOrFail($data['job_id']);

        abort_unless((int) $job->client_id === (int) $data['client_id'], 422);
    }
}