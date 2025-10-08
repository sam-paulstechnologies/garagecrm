<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Client\Client;
use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Job\Booking;

class Communication extends Model
{
    use HasFactory;

    protected $table = 'communications';

    protected $fillable = [
        'client_id',
        'lead_id',
        'opportunity_id',
        'booking_id',
        'company_id',
        'type',                // 'call' | 'email' | 'whatsapp'
        'content',
        'communication_date',
        'follow_up_required',
    ];

    protected $casts = [
        'communication_date' => 'datetime',
        'follow_up_required' => 'boolean',
    ];

    // Relationships
    public function client()      { return $this->belongsTo(Client::class); }
    public function lead()        { return $this->belongsTo(Lead::class); }
    public function opportunity() { return $this->belongsTo(Opportunity::class); }
    public function booking()     { return $this->belongsTo(Booking::class); }

    // Scopes
    public function scopeForCompany($q, $companyId)
    {
        return $q->where('company_id', $companyId);
    }

    public function scopeFilter($q, array $filters)
    {
        if (!empty($filters['client_id']))      { $q->where('client_id', $filters['client_id']); }
        if (!empty($filters['lead_id']))        { $q->where('lead_id', $filters['lead_id']); }
        if (!empty($filters['opportunity_id'])) { $q->where('opportunity_id', $filters['opportunity_id']); }
        if (!empty($filters['booking_id']))     { $q->where('booking_id', $filters['booking_id']); }

        if (!empty($filters['type'])) { $q->where('type', $filters['type']); }

        if (isset($filters['follow_up_required']) && $filters['follow_up_required'] !== '') {
            $q->where('follow_up_required', (int)$filters['follow_up_required'] === 1);
        }
        if (!empty($filters['date_from'])) { $q->whereDate('communication_date', '>=', $filters['date_from']); }
        if (!empty($filters['date_to']))   { $q->whereDate('communication_date', '<=', $filters['date_to']); }
        if (!empty($filters['q']))         { $q->where('content', 'like', '%'.$filters['q'].'%'); }

        return $q;
    }
}
