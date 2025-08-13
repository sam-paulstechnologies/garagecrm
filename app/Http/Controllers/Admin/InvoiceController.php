<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job\Invoice;
use App\Models\Client\Client;
use App\Models\Job\Job;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['client', 'job'])
            ->where('company_id', auth()->user()->company_id)
            ->latest()
            ->paginate(20);

        return view('admin.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        $jobs = Job::where('company_id', auth()->user()->company_id)->get();

        return view('admin.invoices.create', compact('clients', 'jobs'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'  => 'required|exists:clients,id',
            'job_id'     => 'required|exists:jobs,id',
            'amount'     => 'required|numeric|min:0',
            'status'     => 'required|string',
            'due_date'   => 'nullable|date',
        ]);

        $data['company_id'] = auth()->user()->company_id;

        Invoice::create($data);

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice created.');
    }

    public function edit(Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $clients = Client::where('company_id', auth()->user()->company_id)->get();
        $jobs = Job::where('company_id', auth()->user()->company_id)->get();

        return view('admin.invoices.edit', compact('invoice', 'clients', 'jobs'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $data = $request->validate([
            'client_id'  => 'required|exists:clients,id',
            'job_id'     => 'required|exists:jobs,id',
            'amount'     => 'required|numeric|min:0',
            'status'     => 'required|string',
            'due_date'   => 'nullable|date',
        ]);

        $invoice->update($data);

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice)
    {
        $this->authorizeCompany($invoice);
        $invoice->delete();

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice deleted.');
    }

    protected function authorizeCompany(Invoice $invoice)
    {
        abort_if($invoice->company_id !== auth()->user()->company_id, 403);
    }
}
