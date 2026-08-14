<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Model;

class ClientDocument extends Model
{
    protected $table = 'client_documents';

    protected $fillable = [
        'company_id',
        'client_id',
        'document_name',
        'document_path',
        'storage_disk',
        'document_type',
        'uploaded_by',
    ];

    public function resolveRouteBinding($value, $field = null)
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        if (! $companyId) {
            return null;
        }

        return $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('company_id', $companyId)
            ->first();
    }
}
