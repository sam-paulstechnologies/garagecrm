<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadDocumentRequest;
use App\Models\Client\Client;
use App\Models\Job\Job;
use App\Models\Job\JobDocument;
use App\Services\Documents\Ingestion\UploadIngestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class DocumentInboxController extends Controller
{
    public function __construct(protected UploadIngestService $ingest) {}

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function companyId(): int
    {
        $companyId = (int) (auth()->user()?->company_id ?? auth()->user()?->company?->id ?? 0);

        abort_if(! $companyId, 403);

        return $companyId;
    }

    protected function authorizeDocument(JobDocument $doc): int
    {
        $companyId = $this->companyId();

        abort_unless((int) $doc->company_id === $companyId, 404);

        return $companyId;
    }

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $companyId = $this->companyId();

        $query = JobDocument::query()
            ->forCompany($companyId)
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
            $q = trim((string) $request->string('q'));

            $query->where(function ($subQuery) use ($q) {
                $subQuery
                    ->where('original_name', 'like', "%{$q}%")
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
            'type' => (string) $request->query('type', ''),
            'q' => (string) $request->query('q', ''),
        ];

        return view('admin.documents.index', compact('docs', 'filters'));
    }

    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */

    public function show(JobDocument $doc)
    {
        $companyId = $this->authorizeDocument($doc);

        $clients = Client::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name']);

        $jobs = Job::query()
            ->where('company_id', $companyId)
            ->when($doc->client_id, fn ($query) => $query->where('client_id', $doc->client_id))
            ->orderByDesc('id')
            ->limit($doc->client_id ? 500 : 200)
            ->get(['id', 'job_code', 'client_id']);

        return view('admin.documents.show', compact('doc', 'clients', 'jobs'));
    }

    /*
    |--------------------------------------------------------------------------
    | Manual Assignment
    |--------------------------------------------------------------------------
    */

    public function assign(Request $request, JobDocument $doc)
    {
        $companyId = $this->authorizeDocument($doc);

        $data = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('company_id', $companyId),
            ],
            'job_id' => [
                'nullable',
                Rule::exists('jobs', 'id')->where('company_id', $companyId),
            ],
            'type' => ['nullable', Rule::in(['invoice', 'job_card', 'other'])],
            'status' => ['nullable', Rule::in(['assigned', 'needs_review', 'matched'])],
        ]);

        $client = Client::query()
            ->where('company_id', $companyId)
            ->findOrFail($data['client_id']);

        $jobId = $data['job_id'] ?? null;

        if ($jobId) {
            $job = Job::query()
                ->where('company_id', $companyId)
                ->findOrFail($jobId);

            abort_unless((int) $job->client_id === (int) $client->id, 422);
        }

        $doc->fill([
            'company_id' => $companyId,
            'client_id' => $client->id,
            'job_id' => $jobId,
        ]);

        if (! empty($data['type'])) {
            $doc->type = $data['type'];
        }

        $doc->status = $data['status'] ?? 'assigned';
        $doc->save();

        Log::info('doc.assigned', [
            'company_id' => $companyId,
            'doc_id' => $doc->id,
            'by_user' => $request->user()?->id,
            'client_id' => $doc->client_id,
            'job_id' => $doc->job_id,
            'status' => $doc->status,
            'at' => now()->toDateTimeString(),
        ]);

        return redirect()
            ->route('admin.documents.show', $doc)
            ->with('success', 'Document assigned successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Upload Shortcut
    |--------------------------------------------------------------------------
    */

    public function uploadForClient(UploadDocumentRequest $request, Client $client)
    {
        $companyId = $this->companyId();

        abort_unless((int) $client->company_id === $companyId, 404);

        $type = (string) $request->input('type', 'other');

        abort_unless(in_array($type, ['invoice', 'job_card', 'other'], true), 422);

        $doc = $this->ingest->ingestUploadedFile(
            $request->file('file'),
            $type,
            $companyId
        );

        $doc->update([
            'company_id' => $companyId,
            'client_id' => $client->id,
            'status' => 'assigned',
        ]);

        return back()->with('success', 'Document uploaded and assigned.');
    }
}