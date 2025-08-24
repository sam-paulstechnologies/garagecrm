<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomingInvoice extends Model
{
    protected $fillable = [
        'company_id','client_id','invoice_id',
        'whatsapp_from','whatsapp_message_id',
        'original_filename','file_path','file_type',
        'detected_invoice_no','caption','status',
    ];

    public function company() { return $this->belongsTo(\App\Models\Company::class); }
    public function client()  { return $this->belongsTo(\App\Models\Client\Client::class); }
    public function invoice() { return $this->belongsTo(\App\Models\Job\Invoice::class); }
}
