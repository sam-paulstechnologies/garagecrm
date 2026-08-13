<?php

namespace App\Models;

use App\Models\Garage\Garage;
use App\Models\System\Company;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    public const PLATFORM_ROLES = ['super_admin', 'platform_admin'];

    public const TENANT_ROLES = ['admin', 'manager', 'mechanic', 'receptionist', 'supervisor', 'media_team'];

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
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'status' => 'boolean',
        'must_change_password' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_recovery_codes_acknowledged_at' => 'datetime',
        'two_factor_reenrollment_required_at' => 'datetime',
    ];

    protected function password(): Attribute
    {
        return Attribute::make(
            set: fn ($value) => Hash::needsRehash($value) ? Hash::make($value) : $value
        );
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /** @return list<string> */
    public static function platformRoles(): array
    {
        return self::PLATFORM_ROLES;
    }

    /** @return list<string> */
    public static function tenantRoles(): array
    {
        return self::TENANT_ROLES;
    }

    public function isPlatformUser(): bool
    {
        return in_array((string) $this->role, self::PLATFORM_ROLES, true);
    }

    public function hasConfirmedTwoFactorAuthentication(): bool
    {
        return $this->hasEnabledTwoFactorAuthentication()
            && $this->two_factor_recovery_codes_acknowledged_at !== null;
    }
}
