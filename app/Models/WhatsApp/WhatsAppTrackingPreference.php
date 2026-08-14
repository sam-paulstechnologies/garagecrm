<?php

namespace App\Models\WhatsApp;

use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppTrackingPreference extends Model
{
    protected $table = 'whatsapp_tracking_preferences';

    protected $fillable = [
        'company_id', 'external_identity_hash', 'phone_e164', 'decision', 'source_batch_id',
        'source_candidate_id', 'decided_by', 'decided_at', 'history_removed_at',
    ];

    protected $hidden = ['phone_e164'];

    protected $casts = [
        'phone_e164' => 'encrypted', 'decided_at' => 'datetime', 'history_removed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
