<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToCompany;
use App\Models\Client\Client;
use App\Models\Job\Job;

class Invoice extends Model
{
    use HasFactory, BelongsToCompany, SoftDeletes;

    protected $fillable = [
        'company_id',
        'client_id',
        'job_id',
        'file_path',
        'file_type',
        'extracted_text',   // keep nullable for future OCR
        'amount',
        'status',           // enum: pending, paid, overdue
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function client() { return $this->belongsTo(Client::class); }
    public function job()    { return $this->belongsTo(Job::class); }
}