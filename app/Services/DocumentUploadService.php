<?php

namespace App\Services;

use App\Security\Uploads\PrivateUploadStorage;
use Illuminate\Http\UploadedFile;

class DocumentUploadService
{
    public function __construct(private PrivateUploadStorage $storage) {}

    /**
     * Store a file and return metadata.
     *
     * @return array{disk:string,path:string,url:?string,mime:?string,size:int,original_name:string,hash:string}
     */
    public function store(UploadedFile $file, string $dir, ?string $disk = null): array
    {
        abort_if($disk !== null && $disk !== config('security.private_upload_disk'), 500, 'Upload disk overrides are not permitted.');

        $stored = $this->storage->storeUploadedFile($file, $dir);

        return $stored + ['url' => null];
    }
}
