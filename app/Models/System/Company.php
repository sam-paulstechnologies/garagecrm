<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\System\Plan;
use App\Models\User;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',

        /*
        |--------------------------------------------------------------------------
        | Manager / Handoff Details
        |--------------------------------------------------------------------------
        */
        'manager_phone',
        'manager_name',
        'manager_email',

        'address',
        'plan_id',
        'logo',
        'trial_ends_at',

        /*
        |--------------------------------------------------------------------------
        | Launch Setup Fields
        |--------------------------------------------------------------------------
        */
        'legal_name',
        'business_phone',
        'business_email',
        'location_pin',
        'working_hours',
        'booking_rules',
        'service_areas',
        'launch_setup_status',
        'launch_setup_completed_at',

        /*
        |--------------------------------------------------------------------------
        | Meta WhatsApp Credentials
        |--------------------------------------------------------------------------
        */
        'meta_phone_number_id',
        'meta_access_token',
        'meta_verify_token',
        'meta_waba_id',
        'is_whatsapp_active',
        'meta_token_expires_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',

        /*
        |--------------------------------------------------------------------------
        | Launch Setup Casts
        |--------------------------------------------------------------------------
        */
        'working_hours' => 'array',
        'booking_rules' => 'array',
        'service_areas' => 'array',
        'launch_setup_completed_at' => 'datetime',

        /*
        |--------------------------------------------------------------------------
        | WhatsApp Casts
        |--------------------------------------------------------------------------
        */
        'is_whatsapp_active' => 'boolean',
        'meta_token_expires_at' => 'datetime',
    ];

    protected $hidden = [
        'meta_access_token',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isTrialActive(): bool
    {
        return $this->trial_ends_at && now()->lt($this->trial_ends_at);
    }

    public function getActivePlanAttribute()
    {
        if ($this->isTrialActive() && $this->plan) {
            return $this->plan;
        }

        return Plan::find(1); // Freemium fallback
    }

    public function hasMetaWhatsApp(): bool
    {
        return !empty($this->meta_phone_number_id)
            && !empty($this->meta_access_token);
    }

    public function hasActiveMetaWhatsApp(): bool
    {
        return !empty($this->meta_phone_number_id)
            && !empty($this->meta_waba_id)
            && !empty($this->meta_access_token)
            && (bool) ($this->is_whatsapp_active ?? false);
    }

    public function getLaunchSetupCompletionAttribute(): int
    {
        $workingHours = is_array($this->working_hours) ? $this->working_hours : [];
        $bookingRules = is_array($this->booking_rules) ? $this->booking_rules : [];
        $serviceAreas = is_array($this->service_areas) ? $this->service_areas : [];

        $items = [
            !empty($this->legal_name),
            !empty($this->business_phone),
            !empty($this->business_email),
            !empty($this->address),
            !empty($this->location_pin),

            !empty($this->manager_name)
                && !empty($this->manager_phone)
                && !empty($this->manager_email),

            !empty($workingHours['open_time'] ?? null)
                && !empty($workingHours['close_time'] ?? null),

            !empty($bookingRules['max_bookings_per_slot'] ?? null),

            !empty($serviceAreas) && is_array($serviceAreas),

            $this->hasActiveMetaWhatsApp(),
        ];

        $total = count($items);
        $done = collect($items)->filter()->count();

        return $total > 0 ? (int) round(($done / $total) * 100) : 0;
    }

    public function isLaunchSetupComplete(): bool
    {
        return $this->launch_setup_completion >= 100;
    }
}