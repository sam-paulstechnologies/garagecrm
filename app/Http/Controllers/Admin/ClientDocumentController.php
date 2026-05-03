<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\ClientDocument;
use Illuminate\Http\Request;

class ClientDocumentController extends Controller
{
    public function store(Request $request, Client $client)
    {
        $request->validate([
            'document' => 'required|file|max:5120',
            'type'     => 'nullable|string|max:50',
        ]);

        $path = $request->file('document')
            ->store('uploads/client-documents', 'public');

        ClientDocument::create([
            'company_id'    => company_id(),
            'client_id'     => $client->id,
            'document_name' => $request->file('document')->getClientOriginalName(),
            'document_path' => $path,
            'document_type' => $request->input('type', 'other'),
            'uploaded_by'   => auth()->id(),
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }
}
