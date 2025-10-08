<?php 

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use App\Models\System\Plan;
use App\Models\User;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'plan_id',
        'trial_ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
    ];

    // 🔗 Relationships
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    // ✅ Check if company is still under trial
    public function isTrialActive(): bool
    {
        return $this->trial_ends_at && now()->lt($this->trial_ends_at);
    }

    // ✅ Get active plan (trial or fallback to freemium)
    public function getActivePlanAttribute()
    {
        if ($this->isTrialActive() && $this->plan) {
            return $this->plan;
        }

        return Plan::find(1); // Freemium fallback
    }
}
