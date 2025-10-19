<?php

namespace App\Models\Company;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $table = 'company_settings';
    protected $fillable = ['company_id','manager_phone','google_review_link','whatsapp'];
    protected $casts = ['whatsapp' => 'array'];
}
