<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Shared\File;

class Client extends Model
{
    use HasFactory;

    protected $table = 'clients';

    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'phone_norm',     // 🔥 ADD THIS COLUMN IN DB
        'whatsapp',
        'email',
        'email_norm',     // 🔥 ADD THIS COLUMN IN DB
        'dob',
        'gender',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
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

    /*
    |--------------------------------------------------------------------------
    | 🔥 MODEL EVENTS (NORMALIZATION + SAFETY)
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::saving(function ($client) {

            // 🔥 NORMALIZE PHONE
            $client->phone_norm = self::normalizePhone($client->phone);

            // 🔥 NORMALIZE EMAIL
            $client->email_norm = self::normalizeEmail($client->email);

            // 🔥 WHATSAPP FALLBACK
            if (!$client->whatsapp && $client->phone) {
                $client->whatsapp = $client->phone;
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | 🔥 DUPLICATE HELPERS (CRITICAL)
    |--------------------------------------------------------------------------
    */

    public static function findByPhone(int $companyId, ?string $phone): ?self
    {
        if (!$phone) return null;

        return self::where('company_id', $companyId)
            ->where('phone_norm', self::normalizePhone($phone))
            ->first();
    }

    public static function findByEmail(int $companyId, ?string $email): ?self
    {
        if (!$email) return null;

        return self::where('company_id', $companyId)
            ->where('email_norm', self::normalizeEmail($email))
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | 🔥 NORMALIZATION (UAE SAFE)
    |--------------------------------------------------------------------------
    */

    public static function normalizePhone(?string $phone): ?string
    {
        if (!$phone) return null;

        $phone = preg_replace('/\D+/', '', $phone);

        // UAE logic
        if (str_starts_with($phone, '05')) {
            $phone = '971' . substr($phone, 1);
        }

        if (str_starts_with($phone, '9710')) {
            $phone = '971' . substr($phone, 3);
        }

        return $phone ?: null;
    }

    public static function normalizeEmail(?string $email): ?string
    {
        if (!$email) return null;

        $email = trim(mb_strtolower($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function vehicles(): HasMany
    {
        return $this->hasMany(\App\Models\Vehicle\Vehicle::class, 'client_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(\App\Models\Client\Opportunity::class, 'client_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(\App\Models\Client\Lead::class, 'client_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(\App\Models\Client\Note::class, 'client_id')
            ->latest();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(\App\Models\Job\Booking::class, 'client_id')
            ->orderByDesc('created_at');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'client_id')
            ->orderByDesc('uploaded_at');
    }

    public function jobDocuments(): HasMany
    {
        return $this->hasMany(File::class, 'client_id')
            ->whereNotNull('job_id')
            ->orderByDesc('uploaded_at');
    }

    public function documents()
    {
        return $this->hasMany(
            \App\Models\Client\ClientDocument::class,
            'client_id'
        );
    }
}