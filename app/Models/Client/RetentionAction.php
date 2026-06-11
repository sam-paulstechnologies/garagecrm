<?php

namespace App\Models\Client;

use App\Models\System\Company;
use App\Models\User;
use App\Models\Vehicle\Vehicle;
use App\Models\Vehicle\VehicleServiceHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetentionAction extends Model
{
    protected $fillable = [
        'company_id',
        'client_id',
        'vehicle_id',
        'vehicle_service_history_id',
        'source_type',
        'source_id',
        'segment_code',
        'segment_label',
        'last_service_type',
        'last_service_date',
        'suggested_follow_up_date',
        'suggested_message',
        'status',
        'approved_by',
        'approved_at',
        'scheduled_at',
        'sent_at',
        'message_log_id',
        'meta',
    ];

    protected $casts = [
        'last_service_date' => 'date',
        'suggested_follow_up_date' => 'date',
        'approved_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'meta' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function vehicleServiceHistory(): BelongsTo
    {
        return $this->belongsTo(VehicleServiceHistory::class);
    }

    public function importRow(): BelongsTo
    {
        return $this->belongsTo(ClientImportRow::class, 'source_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
