<?php

namespace App\Models\Job;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\BelongsToCompany;
use App\Models\Client\Client;
use App\Models\User;

class Job extends Model
{
    protected $table = 'jobsheets';
    use HasFactory, BelongsToCompany, SoftDeletes;

    protected $table = 'jobs';

    protected $fillable = [
        'client_id',
        'description',
        'status',
        'assigned_to',
        'company_id',
    ];

    // ðŸ”— Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}

