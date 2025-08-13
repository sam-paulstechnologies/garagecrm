<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Shared\File;
use App\Models\Client\Note;



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
        'dob' => 'date',
        'is_vip' => 'boolean',
        'is_archived' => 'boolean',
    ];

    // Relationships
    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    public function files()
    {
        return $this->hasMany(File::class);
    }

    public function notes()
    {
        return $this->hasMany(\App\Models\Client\Note::class);
    }

}
