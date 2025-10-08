<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MetaPage extends Model
{
    protected $table = 'meta_pages';

    protected $fillable = [
        'company_id',
        'page_id',
        'page_name',
        'page_access_token',
        'forms_json',
    ];
}
