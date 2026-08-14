<?php

namespace App\Services\Documents\Ingestion;

use App\Models\Job\JobDocument;
use App\Security\Uploads\PrivateUploadStorage;
use Illuminate\Http\UploadedFile;

class UploadIngestService
{
    public function __construct(private PrivateUploadStorage $storage) {}

    /**
     * Ingest an uploaded file (Admin UI).
     * Returns the JobDocument (existing if deduped).
     */
    public function ingestUploadedFile(UploadedFile $file, string $type = 'other', ?int $companyId = null): JobDocument
    {
        $companyId = (int) ($companyId ?? 0);

        abort_if(! $companyId, 403, 'Missing company context for document upload.');

        $stored = $this->storage->storeUploadedFile(
            $file,
            "companies/{$companyId}/documents/".now()->format('Y/m')
        );
        $hash = $stored['hash'];

        if (config('document_ingest.auto_dedupe', true)) {
            $existing = JobDocument::where('company_id', $companyId)
                ->where('hash', $hash)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return JobDocument::create([
            'company_id' => $companyId,
            'type' => in_array($type, ['invoice', 'job_card', 'other'], true) ? $type : 'other',
            'source' => 'upload',
            'sender_phone' => null,
            'sender_email' => null,
            'provider_message_id' => null,

            'hash' => $hash,
            'original_name' => $stored['original_name'],
            'mime' => $stored['mime'],
            'size' => $stored['size'],
            'path' => $stored['path'],
            'storage_disk' => $stored['disk'],
            'url' => null,

            'status' => 'needs_review',
            'received_at' => now(),
        ]);
    }

    /**
     * Ingest a raw binary blob (for webhooks) and metadata.
     */
    public function ingestRawBinary(string $binary, array $meta): JobDocument
    {
        $companyId = (int) ($meta['company_id'] ?? 0);

        abort_if(! $companyId, 403, 'Missing company context for raw document ingestion.');

        $stored = $this->storage->storeBinary(
            $binary,
            "companies/{$companyId}/documents/".now()->format('Y/m'),
            (string) ($meta['original_name'] ?? 'attachment')
        );
        $hash = $stored['hash'];

        if (config('document_ingest.auto_dedupe', true)) {
            $existing = JobDocument::where('company_id', $companyId)
                ->where('hash', $hash)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return JobDocument::create([
            'company_id' => $companyId,
            'type' => in_array(($meta['type'] ?? 'other'), ['invoice', 'job_card', 'other'], true)
                ? $meta['type']
                : 'other',
            'source' => $meta['source'] ?? 'upload',
            'sender_phone' => $meta['sender_phone'] ?? null,
            'sender_email' => $meta['sender_email'] ?? null,
            'provider_message_id' => $meta['provider_message_id'] ?? null,

            'hash' => $hash,
            'original_name' => $stored['original_name'],
            'mime' => $stored['mime'],
            'size' => $stored['size'],
            'path' => $stored['path'],
            'storage_disk' => $stored['disk'],
            'url' => null,

            'status' => 'needs_review',
            'received_at' => now(),
        ]);
    }
}
