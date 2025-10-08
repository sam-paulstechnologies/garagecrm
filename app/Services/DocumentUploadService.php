<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentUploadService
{
    /**
     * Store a file and return metadata.
     *
     * @return array{disk:string,path:string,url:?string,mime:?string,size:int,original_name:string,hash:string}
     */
    public function store(UploadedFile $file, string $dir, ?string $disk = null): array
    {
        $disk = $disk ?: config('filesystems.default', 'public');
        $dir  = trim($dir, '/');

        // unique filename but also keep a content hash for de-dup if needed
        $hash = sha1_file($file->getRealPath());
        $ext  = $file->getClientOriginalExtension();
        $name = Str::uuid()->toString().($ext ? '.'.$ext : '');

        $path = Storage::disk($disk)->putFileAs($dir, $file, $name);
        $url  = method_exists(Storage::disk($disk), 'url') ? Storage::disk($disk)->url($path) : null;

        return [
            'disk'          => $disk,
            'path'          => $path,
            'url'           => $url,
            'mime'          => $file->getClientMimeType(),
            'size'          => (int) $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
            'hash'          => $hash,
        ];
    }
}
