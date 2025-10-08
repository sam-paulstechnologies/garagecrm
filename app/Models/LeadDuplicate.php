<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadDuplicate extends Model
{
    protected $fillable = [
        'company_id','primary_lead_id',
        'external_source','external_id','external_form_id',
        'name','email','email_norm','phone','phone_norm',
        'matched_on','window_days','reason','payload','detected_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'detected_at' => 'datetime',
    ];

    public function primary(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Client\Lead::class, 'primary_lead_id');
    }
}
