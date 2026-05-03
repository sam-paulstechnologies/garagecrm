<?php

namespace App\Models\Client;

use App\Events\LeadCreated;
use App\Models\MessageLog;
use App\Models\Shared\Communication;
use App\Models\Traits\BelongsToCompany;
use App\Models\User;
use App\Models\Vehicle\VehicleMake;
use App\Models\Vehicle\VehicleModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{
    BelongsTo, HasMany, HasOne
};

class Lead extends Model
{
    use HasFactory, BelongsToCompany;

    /*
    |--------------------------------------------------------------------------
    | Lead Status Constants
    |--------------------------------------------------------------------------
    | IMPORTANT:
    | These must match the actual DB enum:
    | enum('new','attempting_contact','qualified','converted','lost')
    */

    public const STATUS_NEW = 'new';
    public const STATUS_ATTEMPTING = 'attempting_contact';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_LOST = 'lost';

    /*
    |--------------------------------------------------------------------------
    | Compatibility Aliases
    |--------------------------------------------------------------------------
    | These aliases prevent old code from breaking, but they map to DB-safe values.
    | Do not store manager_confirmation_pending in leads.status.
    | Manager confirmation belongs to opportunities.stage only.
    */

    public const STATUS_HOLD = self::STATUS_ATTEMPTING;
    public const STATUS_DISQUALIFIED = self::STATUS_LOST;
    public const STATUS_MANAGER_REVIEW = self::STATUS_ATTEMPTING;
    public const STATUS_MANAGER_CONFIRM = self::STATUS_ATTEMPTING;

    public const ACTIVE_STATUSES = [
        self::STATUS_NEW,
        self::STATUS_ATTEMPTING,
        self::STATUS_QUALIFIED,
    ];

    protected $fillable = [
        'company_id',
        'client_id',
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
        'score',
        'is_active',

        'vehicle_make_id',
        'vehicle_model_id',
        'other_make',
        'other_model',

        'conversation_state',
        'conversation_data',
        'conversation_updated_at',

        'external_source',
        'external_id',
        'external_form_id',
        'external_payload',
        'external_received_at',
    ];

    protected $casts = [
        'last_contacted_at' => 'datetime',
        'external_payload' => 'array',
        'external_received_at' => 'datetime',
        'conversation_data' => 'array',
        'conversation_updated_at' => 'datetime',
        'is_hot' => 'boolean',
        'score' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Lead $lead) {
            $lead->email_norm = self::normalizeEmail($lead->email);
            $lead->phone_norm = self::normalizePhone($lead->phone);

            if (!$lead->status) {
                $lead->status = self::STATUS_NEW;
            }

            /*
            |--------------------------------------------------------------------------
            | Safety Guard
            |--------------------------------------------------------------------------
            | If old code tries to write a non-DB enum value, map it safely.
            */

            $lead->status = self::normalizeStatus($lead->status);

            if (!$lead->isDirty('is_active')) {
                $lead->is_active = in_array(
                    strtolower((string) $lead->status),
                    self::ACTIVE_STATUSES,
                    true
                ) ? 1 : 0;
            }

            if ($lead->isDirty([
                'is_hot',
                'preferred_channel',
                'lead_score_reason',
                'last_contacted_at',
            ])) {
                $lead->score = $lead->calculateScore();
            }

            if ($lead->isDirty(['conversation_state', 'conversation_data'])) {
                $lead->conversation_updated_at = now();
            }
        });

        static::created(function (Lead $lead) {
            event(new LeadCreated($lead));
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
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

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }

    public function messageLogs(): HasMany
    {
        return $this->hasMany(MessageLog::class);
    }

    public function vehicleMake(): BelongsTo
    {
        return $this->belongsTo(VehicleMake::class, 'vehicle_make_id');
    }

    public function vehicleModel(): BelongsTo
    {
        return $this->belongsTo(VehicleModel::class, 'vehicle_model_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Duplicate Helpers
    |--------------------------------------------------------------------------
    */

    public static function findByPhone(int $companyId, ?string $phone): ?self
    {
        if (!$phone) {
            return null;
        }

        return self::where('company_id', $companyId)
            ->where('phone_norm', self::normalizePhone($phone))
            ->latest()
            ->first();
    }

    public static function findByEmail(int $companyId, ?string $email): ?self
    {
        if (!$email) {
            return null;
        }

        return self::where('company_id', $companyId)
            ->where('email_norm', self::normalizeEmail($email))
            ->latest()
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Conversation Helpers
    |--------------------------------------------------------------------------
    */

    public function getConversationMemory(): array
    {
        return is_array($this->conversation_data)
            ? $this->conversation_data
            : [];
    }

    public function setConversationMemory(array $data): void
    {
        $memory = $this->getConversationMemory();

        $this->conversation_data = array_merge($memory, $data);
        $this->save();
    }

    public function getMemoryValue(string $key, $default = null)
    {
        $memory = $this->getConversationMemory();

        return $memory[$key] ?? $default;
    }

    public function clearConversation(): void
    {
        $this->conversation_state = null;
        $this->conversation_data = [];
        $this->conversation_updated_at = now();
        $this->save();
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getVehicleLabelAttribute(): ?string
    {
        $make = $this->vehicleMake?->name ?? $this->other_make;
        $model = $this->vehicleModel?->name ?? $this->other_model;

        $label = trim(($make ?? '') . ' ' . ($model ?? ''));

        return $label !== '' ? $label : null;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ((string) $this->status) {
            self::STATUS_NEW => 'New',
            self::STATUS_ATTEMPTING => 'Attempting Contact',
            self::STATUS_QUALIFIED => 'Qualified',
            self::STATUS_CONVERTED => 'Converted',
            self::STATUS_LOST => 'Lost',
            default => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Normalization
    |--------------------------------------------------------------------------
    */

    public static function normalizeEmail(?string $email): ?string
    {
        if (!$email) {
            return null;
        }

        $email = trim(mb_strtolower($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $phone = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($phone, '05')) {
            $phone = '971' . substr($phone, 1);
        }

        if (str_starts_with($phone, '9710')) {
            $phone = '971' . substr($phone, 3);
        }

        return $phone ?: null;
    }

    public static function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        return match ($status) {
            self::STATUS_NEW,
            self::STATUS_ATTEMPTING,
            self::STATUS_QUALIFIED,
            self::STATUS_CONVERTED,
            self::STATUS_LOST => $status,

            'contact_on_hold',
            'manager_review',
            'manager_confirmation_pending',
            'collecting_details' => self::STATUS_ATTEMPTING,

            'disqualified',
            'closed_lost',
            'lost' => self::STATUS_LOST,

            'closed_won' => self::STATUS_CONVERTED,

            default => self::STATUS_NEW,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Scoring
    |--------------------------------------------------------------------------
    */

    public function calculateScore(): int
    {
        $score = 0;

        if ($this->is_hot) {
            $score += 20;
        }

        if ($this->preferred_channel === 'whatsapp') {
            $score += 10;
        }

        if ($this->lead_score_reason) {
            $score += 10;
        }

        if ($this->last_contacted_at) {
            $score += 5;
        }

        return $score;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isWhatsAppOrigin(): bool
    {
        return strtolower((string) $this->external_source) === 'whatsapp'
            || strtolower((string) $this->source) === 'whatsapp';
    }
}