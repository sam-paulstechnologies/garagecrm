<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Client\Client;

class Invoice extends Model
{
    use SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'company_id','client_id','job_id','source','file_path','url','file_type','mime','size',
        'hash','version','uploaded_by','extracted_text','amount','status','is_primary','number',
        'invoice_date','currency','due_date',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date'     => 'date',
        'is_primary'   => 'boolean',
        'amount'       => 'decimal:2',
    ];

    public function job()    { return $this->belongsTo(Job::class, 'job_id'); }
    public function client() { return $this->belongsTo(Client::class, 'client_id'); }
}
