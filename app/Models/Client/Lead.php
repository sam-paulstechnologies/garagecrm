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
        'phone',
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
    ];

    protected $casts = [
        'last_contacted_at' => 'datetime',
        'is_hot'            => 'boolean',
        'score'             => 'integer',
    ];

    /* -------------------------
     | Relationships
     ------------------------- */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }

    /** Lead owner/assignee (used in controllers/views) */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to')
            ->withDefault(['name' => 'Unassigned']);
    }

    /** Optional: one opportunity created from this lead */
    public function opportunity(): HasOne
    {
        return $this->hasOne(Opportunity::class);
    }

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

    /**
     * Converts this lead to a client and creates an associated opportunity.
     */
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
                'client_id'  => $client->id,
                'lead_id'    => $this->id,
                'stage'      => 'new', // ✅ was 'status'
                'company_id' => $this->company_id,
                'title'      => $this->name . ' Opportunity',
                'assigned_to'=> $this->assigned_to,
                'source'     => $this->source,
                'notes'      => $this->notes,
            ]);

            DB::commit();
            return $client;
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('❌ Lead conversion failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculates and persists a simple lead score.
     */
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
