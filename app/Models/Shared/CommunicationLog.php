<?php

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Model;
use App\Models\Client\Client;
use App\Models\Lead\Lead;
use App\Models\Opportunity\Opportunity;

class CommunicationLog extends Model
{
    protected $table = 'communication_logs';

    // Keep everything you can set via mass-assignment
    protected $fillable = [
        'company_id',
        'client_id',
        'lead_id',
        'opportunity_id',

        'channel',          // call | email | whatsapp
        'direction',        // outbound | inbound
        'template',
        'to_phone',
        'to_email',
        'body',
        'provider_sid',
        'meta',

        'communication_date',
        'follow_up_required',
    ];

    protected $casts = [
        'meta'                => 'array',
        'communication_date'  => 'datetime',
        'follow_up_required'  => 'boolean',
    ];

    // Defaults
    protected $attributes = [
        'follow_up_required' => 0,
    ];

    /* -------------------------
     | Relationships
     * ------------------------*/
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    /* -------------------------
     | Query scopes
     * ------------------------*/
    public function scopeInbound($q)  { return $q->where('direction', 'inbound'); }
    public function scopeOutbound($q) { return $q->where('direction', 'outbound'); }
    public function scopeForChannel($q, string $channel)
    {
        return $q->where('channel', strtolower($channel));
    }
    public function scopeForClient($q, $clientId)
    {
        return $q->where('client_id', $clientId);
    }
    public function scopeRecent($q)
    {
        return $q->orderByDesc('communication_date')->orderByDesc('id');
    }

    /* -------------------------
     | Mutators (normalize input)
     * ------------------------*/
    public function setChannelAttribute($value)
    {
        $this->attributes['channel'] = $value ? strtolower($value) : null;
    }

    public function setDirectionAttribute($value)
    {
        $this->attributes['direction'] = $value ? strtolower($value) : null;
    }
}
