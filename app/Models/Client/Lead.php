<?php

namespace App\Models\Client;

use App\Models\User;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Lead extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'name','email','email_norm','phone','phone_norm','status','source','notes',
        'assigned_to','lead_score_reason','last_contacted_at','preferred_channel',
        'is_hot','company_id','client_id','score',
        // external/meta
        'external_source','external_id','external_form_id','external_payload','external_received_at',
        // vehicle hints
        'vehicle_make_id','vehicle_model_id','other_make','other_model',
        // conversation (NEW)
        'conversation_state','conversation_data',
    ];

    protected $casts = [
        'last_contacted_at'    => 'datetime',
        'is_hot'               => 'boolean',
        'score'                => 'integer',
        'external_payload'     => 'array',
        'external_received_at' => 'datetime',
        // conversation (NEW)
        'conversation_data'    => 'array',
    ];

    public static function normalizeEmail(?string $email): ?string
    {
        $email = trim((string) $email);
        return $email !== '' ? mb_strtolower($email) : null;
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) return null;
        $digits = preg_replace('/\D+/', '', $phone);
        return $digits !== '' ? $digits : null;
    }

    protected static function booted(): void
    {
        static::saving(function (Lead $lead) {
            $lead->email_norm = self::normalizeEmail($lead->email);
            $lead->phone_norm = self::normalizePhone($lead->phone);
        });

        static::created(function (Lead $lead) {
            try {
                // ensure link to existing client if possible
                $lead->attachOrCreateClient();

                // trigger engine (your existing service)
                app(\App\Services\Marketing\TriggerEngine::class)->runForLead($lead);
            } catch (\Throwable $e) {
                \Log::error('TriggerEngine/attach client failed: '.$e->getMessage(), [
                    'lead_id' => $lead->id,
                ]);
            }
        });
    }

    // ---------------- Relations ----------------

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')
            ->withDefault(['name' => 'Unassigned']);
    }

    public function opportunity(): HasOne
    {
        return $this->hasOne(Opportunity::class);
    }

    // helpful for propensity + reporting
    public function messageLogs(): HasMany
    {
        return $this->hasMany(\App\Models\MessageLog::class, 'lead_id');
    }

    // if you use job bookings tied to a lead
    public function bookings(): HasMany
    {
        return $this->hasMany(\App\Models\Job\Booking::class, 'lead_id');
    }

    // ---------------- Scopes ----------------

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // ---------------- Helpers ----------------

    /** Link to an existing Client by email_norm or phone_norm; else create. */
    public function attachOrCreateClient(): Client
    {
        return DB::transaction(function () {
            $client = Client::query()
                ->where('company_id', $this->company_id)
                ->when($this->email_norm, fn($q) => $q->orWhere('email_norm', $this->email_norm))
                ->when($this->phone_norm, fn($q) => $q->orWhere('phone_norm', $this->phone_norm))
                ->first();

            if (!$client) {
                $client = Client::create([
                    'name'        => $this->name,
                    'phone'       => $this->phone,
                    'phone_norm'  => $this->phone_norm,
                    'email'       => $this->email,
                    'email_norm'  => $this->email_norm,
                    'company_id'  => $this->company_id,
                ]);
            }

            if ($this->client_id !== $client->id) {
                $this->client_id = $client->id;
                $this->save();
            }

            return $client;
        });
    }

    /** Convert to client + create an opportunity if missing. */
    public function convertToClient(): ?Client
    {
        try {
            DB::beginTransaction();

            $client = $this->attachOrCreateClient();

            if (!$this->opportunity()->exists()) {
                Opportunity::create([
                    'client_id'        => $client->id,
                    'lead_id'          => $this->id,
                    'stage'            => 'new',
                    'company_id'       => $this->company_id,
                    'title'            => $this->name.' Opportunity',
                    'assigned_to'      => $this->assigned_to,
                    'source'           => $this->source,
                    'notes'            => $this->notes,
                    'vehicle_make_id'  => $this->vehicle_make_id,
                    'vehicle_model_id' => $this->vehicle_model_id,
                    'other_make'       => $this->other_make,
                    'other_model'      => $this->other_model,
                ]);
            }

            $this->status = 'converted';
            $this->save();

            DB::commit();
            return $client;
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('❌ Lead conversion failed: '.$e->getMessage());
            throw $e;
        }
    }

    public function calculateScore(): void
    {
        $score = 0;
        if ($this->is_hot) $score += 20;
        if ($this->preferred_channel === 'whatsapp') $score += 10;
        if (!empty($this->lead_score_reason)) $score += 10;
        if (!empty($this->last_contacted_at)) $score += 5;

        $this->score = $score;
        $this->save();
    }
}
