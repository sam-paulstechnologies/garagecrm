<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PDF;

class InvoiceController extends Controller
{
    public function index()
    {
        $companyId = Auth::user()->company_id;

        $invoices = Invoice::with('client')
            ->where('company_id', $companyId)
            ->get();

        return view('invoices.index', compact('invoices'));
    }

    public function download(Invoice $invoice)
    {
        if ($invoice->company_id !== Auth::user()->company_id) {
            abort(403, 'Unauthorized invoice access.');
        }

        $pdf = PDF::loadView('invoices.pdf', compact('invoice'));
        return $pdf->download('invoice-' . $invoice->id . '.pdf');
    }
}
