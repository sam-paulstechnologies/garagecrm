<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use App\Models\System\Company;
use App\Models\System\Garage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLES = ['admin', 'manager', 'mechanic', 'receptionist', 'supervisor'];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'password',
        'company_id',
        'garage_id',
        'status',
        'must_change_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at'    => 'datetime',
        'status'               => 'integer',
        'must_change_password' => 'boolean',
    ];

    /**
     * Automatically hash password when set (avoid double hashing)
     */
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn($value) => Hash::needsRehash($value)
                ? Hash::make($value)
                : $value,
        );
    }

    /** Relationships */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }
}
