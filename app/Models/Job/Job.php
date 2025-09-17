<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToCompany;
use App\Models\Client\Client;
use App\Models\User;

// ✅ add these imports if you use them elsewhere in the model
use App\Models\Job\JobDocument;
use App\Models\Job\Invoice;
// If you reference bookings on the job, import it (adjust namespace if different)
use App\Models\Booking\Booking;

class Job extends Model
{
    use HasFactory, BelongsToCompany, SoftDeletes;

    protected $table = 'jobs';

    protected $fillable = [
        'company_id',
        'booking_id',
        'client_id',
        'job_code',
        'start_time',
        'end_time',
        'description',
        'work_summary',
        'issues_found',
        'parts_used',
        'total_time_minutes',
        'is_archived',
        'status',          // enum: pending,in_progress,completed
        'assigned_to',
    ];

    protected $casts = [
        'start_time'         => 'datetime',
        'end_time'           => 'datetime',
        'is_archived'        => 'boolean',
        'total_time_minutes' => 'integer',
    ];

    /* ---------------- Relations ---------------- */

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    /** Legacy single-invoice relation some views may use */
    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'job_id', 'id');
    }

    /** 🧾 NEW: list of invoices for this Job */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'job_id', 'id');
    }

    /** 🧾 NEW: the current primary invoice (explicit flag) */
    public function primaryInvoice()
    {
        return $this->hasOne(Invoice::class, 'job_id', 'id')->where('is_primary', true);
    }

    /** Handy accessor for notifications / sharing */
    public function primaryInvoiceUrl(): ?string
    {
        return optional($this->primaryInvoice)->resolved_url ?? null;
    }

    /** 📄 JobDocuments associated with this Job */
    public function jobDocuments()
    {
        return $this->hasMany(JobDocument::class, 'job_id', 'id');
    }
}
