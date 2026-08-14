<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Client\ClientDocument;
use App\Security\Uploads\PrivateUploadStorage;
use Illuminate\Http\Request;

class ClientDocumentController extends Controller
{
    public function __construct(private PrivateUploadStorage $storage) {}

    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    public function store(Request $request, Client $client)
    {
        $companyId = $this->companyId();

        abort_unless((int) $client->company_id === $companyId, 404);

        $request->validate([
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);

        $file = $request->file('document');

        $stored = $this->storage->storeUploadedFile(
            $file,
            "companies/{$companyId}/uploads/client-documents"
        );

        ClientDocument::create([
            'company_id' => $companyId,
            'client_id' => $client->id,
            'document_name' => $file->getClientOriginalName(),
            'document_path' => $stored['path'],
            'storage_disk' => $stored['disk'],
            'document_type' => $request->input('type', 'other'),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function download(Client $client, ClientDocument $document)
    {
        $companyId = $this->companyId();
        abort_unless(
            (int) $client->company_id === $companyId
            && (int) $document->company_id === $companyId
            && (int) $document->client_id === (int) $client->id,
            404
        );

        return $this->storage->download(
            $document->storage_disk,
            $document->document_path,
            $document->document_name,
        );
    }
}
