<?php

namespace App\Models\Vehicle;

use App\Models\Client\Client;
use App\Models\Client\ClientImportRow;
use App\Models\System\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleServiceHistory extends Model
{
    protected $fillable = [
        'company_id',
        'client_id',
        'vehicle_id',
        'source_type',
        'source_id',
        'service_type',
        'service_date',
        'mileage',
        'invoice_amount',
        'currency',
        'notes',
        'raw_payload',
        'meta',
    ];

    protected $casts = [
        'service_date' => 'date',
        'mileage' => 'integer',
        'invoice_amount' => 'decimal:2',
        'raw_payload' => 'array',
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

    public function importRow(): BelongsTo
    {
        return $this->belongsTo(ClientImportRow::class, 'source_id')
            ->where('source_type', 'client_import_row');
    }
}
