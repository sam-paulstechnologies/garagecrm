<?php 

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Model;

class CommunicationLog extends Model
{
    protected $fillable = [
        'client_id',
        'communication_type', // Call, Email, WhatsApp
        'content',
        'communication_date',
        'follow_up_required', // true/false
        'company_id',
    ];

    protected $casts = [
        'communication_date' => 'datetime',
        'follow_up_required' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
