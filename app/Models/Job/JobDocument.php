<?php

namespace App\Models\Job;

use App\Models\Client\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class JobDocument extends Model
{
    protected $fillable = [
        'client_id','job_id',
        'type','source',
        'sender_phone','sender_email','provider_message_id',
        'hash','original_name','mime','size',
        'path','url',
        'status','received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    // Accessor: prefer stored URL; fallback to disk URL
    public function getPublicUrlAttribute(): ?string
    {
        if (!empty($this->url)) {
            return $this->url;
        }
        $disk = config('document_ingest.public_disk', config('filesystems.default', 'public'));
        if ($this->path && Storage::disk($disk)->exists($this->path)) {
            return Storage::disk($disk)->url($this->path);
        }
        return null;
    }
}
