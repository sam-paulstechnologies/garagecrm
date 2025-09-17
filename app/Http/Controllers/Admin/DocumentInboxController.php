<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentRequest;
use App\Models\Client\Client;
use App\Models\Job\Job;
use App\Models\Job\JobDocument;
use App\Services\Documents\Ingestion\UploadIngestService;
use Illuminate\Http\Request;

class DocumentInboxController extends Controller
{
    public function __construct(protected UploadIngestService $ingest) {}

    public function index(Request $request)
    {
        $query = JobDocument::query()
            ->latest('received_at')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }
        if ($request->filled('source')) {
            $query->where('source', (string) $request->string('source'));
        }
        if ($request->filled('type')) {
            $query->where('type', (string) $request->string('type'));
        }
        if ($request->filled('q')) {
            $q = (string) $request->string('q');
            $query->where(function ($qq) use ($q) {
                $qq->where('original_name', 'like', "%{$q}%")
                   ->orWhere('sender_email', 'like', "%{$q}%")
                   ->orWhere('sender_phone', 'like', "%{$q}%")
                   ->orWhere('provider_message_id', 'like', "%{$q}%")
                   ->orWhere('hash', 'like', "%{$q}%");
            });
        }

        $docs = $query
            ->paginate((int) config('document_ingest.inbox_page_size', 20))
            ->withQueryString();

        $filters = [
            'status' => (string) $request->query('status', ''),
            'source' => (string) $request->query('source', ''),
            'type'   => (string) $request->query('type', ''),
            'q'      => (string) $request->query('q', ''),
        ];

        return view('admin.documents.index', compact('docs', 'filters'));
    }

    public function show(JobDocument $doc)
    {
        $clients = Client::orderBy('name')
            ->limit(500)
            ->get(['id','name']);

        $jobs = $doc->client_id
            ? Job::where('client_id', $doc->client_id)->orderByDesc('id')->limit(500)->get(['id','job_code'])
            : Job::orderByDesc('id')->limit(200)->get(['id','job_code']);

        return view('admin.documents.show', compact('doc', 'clients', 'jobs'));
    }

    /**
     * Manual assignment (status -> assigned by default)
     */
    public function assign(Request $request, JobDocument $doc)
    {
        $data = $request->validate([
            'client_id' => ['required','exists:clients,id'],
            'job_id'    => ['nullable','exists:jobs,id'],
            'type'      => ['nullable','in:invoice,job_card,other'],
            'status'    => ['nullable','in:assigned,needs_review,matched'],
        ]);

        $doc->fill([
            'client_id' => $data['client_id'],
            'job_id'    => $data['job_id'] ?? null,
        ]);

        if (!empty($data['type'])) {
            $doc->type = $data['type'];
        }

        $doc->status = $data['status'] ?? 'assigned';
        $doc->save();

        // Lightweight audit log
        \Log::info('doc.assigned', [
            'doc_id'    => $doc->id,
            'by_user'   => optional($request->user())->id,
            'client_id' => $doc->client_id,
            'job_id'    => $doc->job_id,
            'status'    => $doc->status,
            'at'        => now()->toDateTimeString(),
        ]);

        return redirect()
            ->route('admin.documents.show', $doc)
            ->with('success', 'Document assigned successfully.');
    }

    /**
     * Admin upload shortcut (e.g., from client page)
     */
    public function uploadForClient(UploadDocumentRequest $request, Client $client)
    {
        $type = (string) $request->input('type', 'other');

        $doc = $this->ingest->ingestUploadedFile($request->file('file'), $type);

        $doc->update([
            'client_id' => $client->id,
            'status'    => 'assigned',
        ]);

        return back()->with('success', 'Document uploaded and assigned.');
    }
}
