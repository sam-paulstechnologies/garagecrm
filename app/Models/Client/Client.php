<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Client\Lead;
use App\Models\Client\Opportunity;
use App\Models\Shared\File;
use App\Models\Client\Note;
use App\Models\Vehicle\Vehicle;
use App\Models\Job\Job;
use App\Models\Job\Invoice;
use App\Models\Job\JobDocument;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'whatsapp',
        'dob',
        'gender',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'email',
        'source',
        'status',
        'notes',
        'is_vip',
        'preferred_channel',
        'is_archived',
    ];

    protected $casts = [
        'dob'         => 'date',
        'is_vip'      => 'boolean',
        'is_archived' => 'boolean',
    ];

    /* ---------------- Relations ---------------- */

    public function leads()
    {
        return $this->hasMany(Lead::class, 'client_id', 'id');
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class, 'client_id', 'id');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'client_id', 'id');
    }

    public function notes()
    {
        return $this->hasMany(Note::class, 'client_id', 'id');
    }

    /** 🚗 Client → Vehicles */
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'client_id', 'id');
    }

    /** 🧾 Invoices linked to this client */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'client_id', 'id');
    }

    /** 🔧 Service Jobs linked to client */
    public function jobs()
    {
        return $this->hasMany(Job::class, 'client_id', 'id');
    }

    /** 📄 Documents assigned to this client */
    public function jobDocuments()
    {
        return $this->hasMany(JobDocument::class, 'client_id', 'id');
    }
}
