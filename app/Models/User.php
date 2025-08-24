<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use App\Models\System\Company;
use App\Models\System\Garage;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /** Single source of truth for allowed roles */
    public const ROLES = ['admin', 'manager', 'mechanic', 'receptionist', 'supervisor'];

    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'password',
        'company_id',
        'garage_id',
        'status',                // 1 = active, 0 = inactive
        'must_change_password',  // optional column
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'status'            => 'integer',
        'must_change_password' => 'boolean',
    ];

    // 🔒 Auto-hash password when set. Do NOT Hash::make() before setting.
    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => Hash::make($value),
        );
    }

    // 🔗 Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }
}
