<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\ClientDocument;
use Illuminate\Http\Request;

class ClientDocumentController extends Controller
{
    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(!$companyId, 403);

        return $companyId;
    }

    public function store(Request $request, Client $client)
    {
        $companyId = $this->companyId();

        abort_unless((int) $client->company_id === $companyId, 404);

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'type'     => ['nullable', 'string', 'max:50'],
        ]);

        $file = $request->file('document');

        $path = $file->store(
            "companies/{$companyId}/uploads/client-documents",
            'public'
        );

        ClientDocument::create([
            'company_id'    => $companyId,
            'client_id'     => $client->id,
            'document_name' => $file->getClientOriginalName(),
            'document_path' => $path,
            'document_type' => $request->input('type', 'other'),
            'uploaded_by'   => auth()->id(),
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }
}