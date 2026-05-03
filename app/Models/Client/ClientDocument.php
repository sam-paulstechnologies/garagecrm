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
        'document_type',
        'uploaded_by',
    ];
}
