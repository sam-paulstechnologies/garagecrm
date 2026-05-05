<?php

namespace App\Models\Job;

use App\Models\Client\Client;
use Illuminate\Database\Eloquent\Model;

class JobDocument extends Model
{
    protected $table = 'job_documents';

    protected $fillable = [
        'company_id',
        'client_id',
        'job_id',
        'type',
        'source',
        'sender_phone',
        'sender_email',
        'provider_message_id',
        'hash',
        'original_name',
        'mime',
        'size',
        'path',
        'url',
        'status',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | ROUTE MODEL BINDING SAFETY
    |--------------------------------------------------------------------------
    */

    public function resolveRouteBinding($value, $field = null)
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        if (!$companyId) {
            return null;
        }

        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->where('company_id', $companyId)
            ->first();
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}