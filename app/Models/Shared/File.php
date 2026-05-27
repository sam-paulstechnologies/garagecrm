<?php

namespace App\Models\Shared;

use App\Models\Client\Client;
use App\Models\Job\Booking;
use App\Models\Job\Job;
use App\Models\Job\Invoice;
use App\Models\System\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'files';

    protected $fillable = [
        'company_id',
        'client_id',
        'booking_id',
        'job_id',
        'invoice_id',
        'file_name',
        'file_path',
        'file_type',
        'category',
        'uploaded_by',
        'notes',
        'uploaded_at',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'booking_id' => 'integer',
        'job_id' => 'integer',
        'invoice_id' => 'integer',
        'uploaded_by' => 'integer',
        'uploaded_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Route Model Binding Safety
    |--------------------------------------------------------------------------
    | Prevents /files/{file} style access from resolving records outside the
    | authenticated user's company.
    |--------------------------------------------------------------------------
    */
    public function resolveRouteBinding($value, $field = null)
    {
        $companyId = (int) (auth()->user()?->company_id ?? 0);

        if (! $companyId) {
            return null;
        }

        return $this->newQuery()
            ->where($field ?? $this->getRouteKeyName(), $value)
            ->where('company_id', $companyId)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')
            ->withDefault(['name' => 'System']);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForBooking($query, int $bookingId)
    {
        return $query->where('booking_id', $bookingId);
    }

    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    public function scopeForInvoice($query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('uploaded_at')->limit($limit);
    }
}