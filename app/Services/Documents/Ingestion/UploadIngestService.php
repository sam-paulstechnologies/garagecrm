<?php

namespace App\Services\Documents\Ingestion;

use App\Models\Job\JobDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadIngestService
{
    protected string $disk;

    public function __construct(?string $disk = null)
    {
        // Use configured disk or fall back to 'public'
        $this->disk = $disk ?: config('document_ingest.public_disk', 'public');
    }

    /**
     * Ingest an uploaded file (Admin UI).
     * Returns the JobDocument (existing if deduped).
     */
    public function ingestUploadedFile(UploadedFile $file, string $type = 'other'): JobDocument
    {
        $ext  = strtolower($file->getClientOriginalExtension()) ?: 'bin';
        $orig = $file->getClientOriginalName();
        $mime = $file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream';
        $size = (int) ($file->getSize() ?? 0);

        // Hash for dedupe
        $hash = hash_file('sha256', $file->getRealPath());

        if (config('document_ingest.auto_dedupe', true)) {
            if ($existing = JobDocument::where('hash', $hash)->first()) {
                return $existing;
            }
        }

        // Path: docs/YYYY/MM/hash.ext
        $subdir   = now()->format('Y/m');
        $filename = $hash . '.' . $ext;
        $path     = "docs/{$subdir}/{$filename}";

        Storage::disk($this->disk)->put($path, file_get_contents($file->getRealPath()));
        $url = Storage::disk($this->disk)->url($path);

        return JobDocument::create([
            'type'                => in_array($type, ['invoice','job_card','other'], true) ? $type : 'other',
            'source'              => 'upload',
            'sender_phone'        => null,
            'sender_email'        => null,
            'provider_message_id' => null,

            'hash'                => $hash,
            'original_name'       => $orig,
            'mime'                => $mime,
            'size'                => $size,
            'path'                => $path,
            'url'                 => $url,

            'status'              => 'needs_review',
            'received_at'         => now(),
        ]);
    }

    /**
     * Ingest a raw binary blob (for webhooks) and metadata.
     */
    public function ingestRawBinary(string $binary, array $meta): JobDocument
    {
        $hash = hash('sha256', $binary);

        if (config('document_ingest.auto_dedupe', true)) {
            if ($exist = JobDocument::where('hash', $hash)->first()) {
                return $exist;
            }
        }

        $ext    = $this->inferExtension($meta['mime'] ?? null, $meta['original_name'] ?? null);
        $subdir = now()->format('Y/m');
        $path   = "docs/{$subdir}/{$hash}.{$ext}";

        Storage::disk($this->disk)->put($path, $binary);
        $url = Storage::disk($this->disk)->url($path);

        return JobDocument::create([
            'type'                => $meta['type'] ?? 'other',
            'source'              => $meta['source'] ?? 'upload',
            'sender_phone'        => $meta['sender_phone'] ?? null,
            'sender_email'        => $meta['sender_email'] ?? null,
            'provider_message_id' => $meta['provider_message_id'] ?? null,

            'hash'                => $hash,
            'original_name'       => $meta['original_name'] ?? ($hash . '.' . $ext),
            'mime'                => $meta['mime'] ?? null,
            'size'                => $meta['size'] ?? strlen($binary),
            'path'                => $path,
            'url'                 => $url,

            'status'              => 'needs_review',
            'received_at'         => now(),
        ]);
    }

    protected function inferExtension(?string $mime, ?string $original): string
    {
        $map = [
            'application/pdf' => 'pdf',
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/jpg'       => 'jpg',
            'image/gif'       => 'gif',
            'image/svg+xml'   => 'svg',
        ];
        if ($mime && isset($map[$mime])) return $map[$mime];
        if ($original && str_contains($original, '.')) {
            return strtolower(pathinfo($original, PATHINFO_EXTENSION)) ?: 'bin';
        }
        return 'bin';
        }
}
