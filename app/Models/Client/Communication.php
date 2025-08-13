<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client\Client;
use App\Models\System\Company;

class Communication extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'communication_type', // e.g., Call, Email, WhatsApp
        'content',
        'communication_date',
        'follow_up_required',
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

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
