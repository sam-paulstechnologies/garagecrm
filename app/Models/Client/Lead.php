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
        'name',
        'email',
        'email_norm',
        'phone',
        'phone_norm',
        'status',
        'source',
        'notes',
        'assigned_to',
        'lead_score_reason',
        'last_contacted_at',
        'preferred_channel',
        'is_hot',
        'company_id',
        'client_id',
        'score',
        // external/meta
        'external_source',
        'external_id',
        'external_form_id',
        'external_payload',
        'external_received_at',
    ];

    protected $casts = [
        'last_contacted_at'    => 'datetime',
        'is_hot'               => 'boolean',
        'score'                => 'integer',
        'external_payload'     => 'array',
        'external_received_at' => 'datetime',
    ];

    /* -------------------------
     | Normalization helpers
     ------------------------- */
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

    // Auto-fill normalized columns on save AND fire triggers on create
    protected static function booted(): void
    {
        static::saving(function (Lead $lead) {
            $lead->email_norm = self::normalizeEmail($lead->email);
            $lead->phone_norm = self::normalizePhone($lead->phone);
        });

        // 🔔 When a lead is created, run the trigger engine
        static::created(function (Lead $lead) {
            try {
                app(\App\Services\Marketing\TriggerEngine::class)->runForLead($lead);
            } catch (\Throwable $e) {
                \Log::error('TriggerEngine failed for lead.created: '.$e->getMessage(), [
                    'lead_id' => $lead->id,
                    'trace'   => $e->getTraceAsString(),
                ]);
            }
        });
    }

    /* -------------------------
     | Relationships
     ------------------------- */
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }

    public function communications(): HasMany { return $this->hasMany(Communication::class); }

    /** Lead owner/assignee (used in controllers/views) */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')
            ->withDefault(['name' => 'Unassigned']);
    }

    /** Optional: one opportunity created from this lead */
    public function opportunity(): HasOne { return $this->hasOne(Opportunity::class); }

    /* -------------------------
     | Scopes
     ------------------------- */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /* -------------------------
     | Domain logic
     ------------------------- */

    public function convertToClient(): ?Client
    {
        try {
            DB::beginTransaction();

            $client = Client::create([
                'name'       => $this->name,
                'phone'      => $this->phone,
                'email'      => $this->email,
                'company_id' => $this->company_id,
            ]);

            $this->client_id = $client->id;
            $this->status    = 'converted';
            $this->save();

            Opportunity::create([
                'client_id'   => $client->id,
                'lead_id'     => $this->id,
                'stage'       => 'new',
                'company_id'  => $this->company_id,
                'title'       => $this->name.' Opportunity',
                'assigned_to' => $this->assigned_to,
                'source'      => $this->source,
                'notes'       => $this->notes,
            ]);

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
