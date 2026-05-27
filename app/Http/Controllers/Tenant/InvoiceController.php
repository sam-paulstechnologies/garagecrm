<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use PDF;

class InvoiceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function companyId(): int
    {
        $companyId = (int) (Auth::user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    protected function invoiceScope()
    {
        return Invoice::query()
            ->where('company_id', $this->companyId());
    }

    protected function authorizeCompany(Invoice $invoice): void
    {
        abort_unless((int) $invoice->company_id === $this->companyId(), 404);
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $companyId = $this->companyId();

        $invoices = Invoice::query()
            ->with([
                'client' => fn ($query) => $query->where('company_id', $companyId),
            ])
            ->where('company_id', $companyId)
            ->latest('id')
            ->paginate(20);

        return view('invoices.index', compact('invoices'));
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    | Kept because tenant invoice resource routes exist.
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        return view('invoices.create');
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
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['pending', 'paid', 'overdue'])],
            'due_date' => ['required', 'date'],
        ]);

        $invoice = new Invoice();
        $invoice->company_id = $companyId;
        $invoice->client_id = $data['client_id'];
        $invoice->amount = $data['amount'];
        $invoice->status = $data['status'] ?? 'pending';
        $invoice->due_date = $data['due_date'];
        $invoice->save();

        return redirect()
            ->route('tenant.invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $companyId = $this->companyId();

        $invoice->load([
            'client' => fn ($query) => $query->where('company_id', $companyId),
        ]);

        return view('invoices.show', compact('invoice'));
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */

    public function edit(Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        return view('invoices.edit', compact('invoice'));
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $companyId = $this->companyId();

        $data = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('company_id', $companyId),
            ],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['pending', 'paid', 'overdue'])],
            'due_date' => ['required', 'date'],
        ]);

        $invoice->update([
            'client_id' => $data['client_id'],
            'amount' => $data['amount'],
            'status' => $data['status'],
            'due_date' => $data['due_date'],
        ]);

        return redirect()
            ->route('tenant.invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */

    public function destroy(Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $invoice->delete();

        return redirect()
            ->route('tenant.invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Download
    |--------------------------------------------------------------------------
    */

    public function download(Invoice $invoice)
    {
        $this->authorizeCompany($invoice);

        $companyId = $this->companyId();

        $invoice->load([
            'client' => fn ($query) => $query->where('company_id', $companyId),
        ]);

        $pdf = PDF::loadView('invoices.pdf', compact('invoice'));

        return $pdf->download('invoice-' . $invoice->id . '.pdf');
    }
}