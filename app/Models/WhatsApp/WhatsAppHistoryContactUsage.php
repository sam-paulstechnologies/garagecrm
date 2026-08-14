<?php

namespace App\Models\WhatsApp;

use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppHistoryContactUsage extends Model
{
    protected $table = 'whatsapp_history_contact_usages';

    protected $fillable = [
        'company_id', 'external_identity_hash', 'first_batch_id', 'first_candidate_id',
        'allowance_snapshot', 'entitlement_source', 'analysed_at', 'imported_at',
    ];

    protected $casts = ['analysed_at' => 'datetime', 'imported_at' => 'datetime'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
