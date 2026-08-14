<?php

namespace App\Security\Uploads;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PrivateUploadStorage
{
    /** @return array{disk:string,path:string,mime:string,size:int,original_name:string,hash:string} */
    public function storeUploadedFile(UploadedFile $file, string $directory): array
    {
        $realPath = $file->getRealPath();
        if (! is_string($realPath) || $realPath === '') {
            throw ValidationException::withMessages(['file' => 'The uploaded file could not be inspected.']);
        }

        $binary = file_get_contents($realPath);
        if (! is_string($binary)) {
            throw ValidationException::withMessages(['file' => 'The uploaded file could not be read safely.']);
        }

        return $this->storeBinary($binary, $directory, $file->getClientOriginalName());
    }

    /** @return array{disk:string,path:string,mime:string,size:int,original_name:string,hash:string} */
    public function storeBinary(string $binary, string $directory, string $originalName): array
    {
        $size = strlen($binary);
        $max = max(1, (int) config('security.upload_max_bytes', 20 * 1024 * 1024));
        if ($size < 1 || $size > $max) {
            throw ValidationException::withMessages(['file' => 'The file is empty or exceeds the approved size limit.']);
        }

        $mime = $this->detectMime($binary);
        $allowed = (array) config('security.upload_allowed_mimes', []);
        $extension = $allowed[$mime] ?? null;
        if (! is_string($extension) || $extension === '') {
            throw ValidationException::withMessages(['file' => 'This file content type is not allowed.']);
        }

        $disk = $this->privateDisk();
        $hash = hash('sha256', $binary);
        $directory = $this->safeRelativePath($directory);
        $path = $directory.'/'.$hash.'.'.$extension;

        if (! Storage::disk($disk)->put($path, $binary)) {
            throw new RuntimeException('The private upload could not be persisted.');
        }

        return [
            'disk' => $disk,
            'path' => $path,
            'mime' => $mime,
            'size' => $size,
            'original_name' => $this->safeOriginalName($originalName),
            'hash' => $hash,
        ];
    }

    public function download(
        ?string $recordedDisk,
        string $path,
        string $filename,
        ?string $mime = null,
        bool $inline = false,
    ): StreamedResponse {
        [$disk, $safePath] = $this->locate($recordedDisk, $path);
        $stream = Storage::disk($disk)->readStream($safePath);
        abort_unless(is_resource($stream), 404);

        $mime = $mime && array_key_exists(strtolower($mime), (array) config('security.upload_allowed_mimes', []))
            ? strtolower($mime)
            : (Storage::disk($disk)->mimeType($safePath) ?: 'application/octet-stream');
        $disposition = HeaderUtils::makeDisposition(
            $inline ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
            $this->safeOriginalName($filename),
            'download'
        );

        return response()->streamDownload(static function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, null, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function delete(?string $recordedDisk, string $path): void
    {
        try {
            [$disk, $safePath] = $this->locate($recordedDisk, $path);
            Storage::disk($disk)->delete($safePath);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            // The database record can still be removed when the blob is already gone.
        }
    }

    /** @return array{0:string,1:string} */
    private function locate(?string $recordedDisk, string $path): array
    {
        $safePath = $this->safeRelativePath($path);
        $privateDisk = $this->privateDisk();
        $candidates = $recordedDisk
            ? [trim($recordedDisk)]
            : array_values(array_unique([$privateDisk, 'public']));

        foreach ($candidates as $disk) {
            abort_unless(in_array($disk, [$privateDisk, 'public'], true), 404);
            if (Storage::disk($disk)->exists($safePath)) {
                return [$disk, $safePath];
            }
        }

        abort(404);
    }

    private function privateDisk(): string
    {
        $disk = trim((string) config('security.private_upload_disk', 'local'));
        if ($disk === '' || $disk === 'public' || ! array_key_exists($disk, (array) config('filesystems.disks', []))) {
            throw new RuntimeException('A non-public private upload disk must be configured.');
        }

        return $disk;
    }

    private function detectMime(string $binary): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return strtolower((string) $finfo->buffer($binary));
    }

    private function safeRelativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        abort_if($path === '' || str_contains($path, '../') || str_contains($path, "\0") || preg_match('/\A[a-zA-Z]:/', $path), 404);

        return $path;
    }

    private function safeOriginalName(string $name): string
    {
        $name = preg_replace('/[\x00-\x1F\x7F]+/u', '', basename(str_replace('\\', '/', $name))) ?? '';
        $name = trim($name);

        return mb_substr($name !== '' ? $name : 'download', 0, 180);
    }
}
