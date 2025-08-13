<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Client\Client;
use App\Models\Client\Communication;
use App\Models\Client\Opportunity;
use App\Models\Traits\BelongsToCompany;

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

    // Relationships
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function communications()
    {
        return $this->hasMany(Communication::class);
    }

    /**
     * Converts a lead into a client and creates an associated opportunity.
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
            $this->status = 'converted';
            $this->save();

            Opportunity::create([
                'client_id'    => $client->id,
                'lead_id'      => $this->id,
                'status'       => 'new',
                'company_id'   => $this->company_id,
                'title'        => $this->name . ' Opportunity',
            ]);

            DB::commit();
            return $client;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('❌ Lead conversion failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Calculates a basic lead score.
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
