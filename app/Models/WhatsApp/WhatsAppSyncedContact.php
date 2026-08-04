<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSyncedContact extends Model
{
    protected $table = 'whatsapp_synced_contacts';

    protected $fillable = [
        'company_id',
        'phone_number_id',
        'contact_hash',
        'contact_phone',
        'full_name',
        'first_name',
        'sync_action',
        'status',
        'provider_timestamp',
        'last_synced_at',
    ];

    protected $hidden = ['contact_phone', 'full_name', 'first_name'];

    protected $casts = [
        'contact_phone' => 'encrypted',
        'full_name' => 'encrypted',
        'first_name' => 'encrypted',
        'provider_timestamp' => 'datetime',
        'last_synced_at' => 'datetime',
    ];
}
