<?php

namespace App\Models\Client;

use App\Events\LeadCreated;
use App\Models\LeadSource;
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

    public const STATUS_NEW = 'new';
    public const STATUS_ATTEMPTING = 'attempting_contact';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_CONVERTED = 'converted';
    public const STATUS_LOST = 'lost';

    public const STATUS_HOLD = self::STATUS_ATTEMPTING;
    public const STATUS_DISQUALIFIED = self::STATUS_LOST;
    public const STATUS_MANAGER_REVIEW = self::STATUS_ATTEMPTING;
    public const STATUS_MANAGER_CONFIRM = self::STATUS_ATTEMPTING;

    public const CONVERSATION_AWAITING_INTENT = 'awaiting_intent';
    public const CONVERSATION_AWAITING_VEHICLE = 'awaiting_vehicle';
    public const CONVERSATION_AWAITING_TIMESLOT = 'awaiting_timeslot';
    public const CONVERSATION_HUMAN = 'human';
    public const CONVERSATION_IDLE = 'idle';

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
        'lead_source_id',
        'notes',

        'assigned_to',
        'lead_score_reason',
        'last_contacted_at',
        'preferred_channel',

        'is_hot',
        'score',
        'is_active',

        /*
        |--------------------------------------------------------------------------
        | Lead Import / Categorization Fields
        |--------------------------------------------------------------------------
        */
        'service_category',
        'service_type',
        'vehicle_make',
        'vehicle_model',
        'vehicle_year',
        'plate_number',
        'lead_temperature',
        'lead_priority',
        'customer_type',
        'follow_up_required',
        'follow_up_date',
        'campaign_name',
        'retention_tag',

        /*
        |--------------------------------------------------------------------------
        | Existing Vehicle Resolver Fields
        |--------------------------------------------------------------------------
        */
        'vehicle_make_id',
        'vehicle_model_id',
        'other_make',
        'other_model',

        /*
        |--------------------------------------------------------------------------
        | Conversation Engine Fields
        |--------------------------------------------------------------------------
        */
        'conversation_state',
        'conversation_data',
        'conversation_updated_at',

        /*
        |--------------------------------------------------------------------------
        | External Source Fields
        |--------------------------------------------------------------------------
        */
        'external_source',
        'external_id',
        'external_form_id',
        'external_payload',
        'external_received_at',
    ];

    protected $casts = [
        'lead_source_id' => 'integer',

        'last_contacted_at' => 'datetime',

        'vehicle_year' => 'integer',
        'follow_up_required' => 'boolean',
        'follow_up_date' => 'date',

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

            if (! $lead->status) {
                $lead->status = self::STATUS_NEW;
            }

            $lead->status = self::normalizeStatus($lead->status);

            if (! $lead->isDirty('is_active')) {
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
                'status',
                'phone',
                'email',
                'vehicle_make_id',
                'vehicle_model_id',
                'other_make',
                'other_model',
                'service_category',
                'service_type',
                'vehicle_make',
                'vehicle_model',
                'vehicle_year',
                'lead_temperature',
                'lead_priority',
                'customer_type',
                'follow_up_required',
                'follow_up_date',
                'retention_tag',
                'conversation_data',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'lead_source_id');
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

    public static function findByPhone(int $companyId, ?string $phone): ?self
    {
        if (! $phone) {
            return null;
        }

        return self::where('company_id', $companyId)
            ->where('phone_norm', self::normalizePhone($phone))
            ->latest()
            ->first();
    }

    public static function findByEmail(int $companyId, ?string $email): ?self
    {
        if (! $email) {
            return null;
        }

        return self::where('company_id', $companyId)
            ->where('email_norm', self::normalizeEmail($email))
            ->latest()
            ->first();
    }

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

    public function moveToBookingFlow(?string $serviceType = null): void
    {
        $memory = $this->getConversationMemory();

        if ($serviceType) {
            $memory['service_type'] = $serviceType;
            $memory['tentative_service_type'] = $serviceType;
        }

        $this->conversation_state = self::CONVERSATION_AWAITING_TIMESLOT;
        $this->conversation_data = $memory;
        $this->conversation_updated_at = now();
        $this->save();
    }

    public function getVehicleLabelAttribute(): ?string
    {
        $make = $this->vehicleMake?->name
            ?? $this->other_make
            ?? $this->vehicle_make;

        $model = $this->vehicleModel?->name
            ?? $this->other_model
            ?? $this->vehicle_model;

        $label = trim(($make ?? '') . ' ' . ($model ?? ''));

        return $label !== '' ? $label : null;
    }

    public function getTentativeServiceTypeAttribute(): ?string
    {
        return $this->service_type
            ?? $this->getMemoryValue('tentative_service_type')
            ?? $this->getMemoryValue('service_type');
    }

    public function getLastServiceTypeAttribute(): ?string
    {
        return $this->getMemoryValue('last_service_type');
    }

    public function getLastServiceDateAttribute(): ?string
    {
        return $this->getMemoryValue('last_service_date');
    }

    public function getMulkiaExpiryDateAttribute(): ?string
    {
        return $this->getMemoryValue('mulkia_expiry_date');
    }

    public function getInsuranceExpiryDateAttribute(): ?string
    {
        return $this->getMemoryValue('insurance_expiry_date');
    }

    public function getNextPingDateAttribute(): ?string
    {
        return $this->getMemoryValue('next_ping_date');
    }

    public function getRetentionBucketAttribute(): ?string
    {
        return $this->retention_tag
            ?? $this->getMemoryValue('retention_bucket');
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

    public function getLeadTemperatureLabelAttribute(): string
    {
        return $this->labelize($this->lead_temperature ?: 'unknown');
    }

    public function getLeadPriorityLabelAttribute(): string
    {
        return $this->labelize($this->lead_priority ?: 'normal');
    }

    public function getServiceCategoryLabelAttribute(): string
    {
        return $this->labelize($this->service_category ?: 'uncategorized');
    }

    public static function normalizeEmail(?string $email): ?string
    {
        if (! $email) {
            return null;
        }

        $email = trim(mb_strtolower($email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public static function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $phone = trim((string) $phone);

        if (stripos($phone, 'E+') !== false || stripos($phone, 'E-') !== false) {
            $phone = number_format((float) $phone, 0, '', '');
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

    public function calculateScore(): int
    {
        $score = 0;

        if ($this->phone || $this->phone_norm) {
            $score += 20;
        }

        if ($this->email) {
            $score += 5;
        }

        if ($this->preferred_channel === 'whatsapp') {
            $score += 10;
        }

        if ($this->is_hot) {
            $score += 30;
        }

        if ($this->lead_score_reason) {
            $score += 10;
        }

        if ($this->last_contacted_at) {
            $score += 5;
        }

        if (
            $this->vehicle_make_id ||
            $this->vehicle_model_id ||
            $this->other_make ||
            $this->other_model ||
            $this->vehicle_make ||
            $this->vehicle_model ||
            $this->plate_number
        ) {
            $score += 15;
        }

        if (
            $this->service_type ||
            $this->service_category ||
            $this->getMemoryValue('service_type') ||
            $this->getMemoryValue('tentative_service_type')
        ) {
            $score += 10;
        }

        if ($this->follow_up_required) {
            $score += 5;
        }

        if ($this->follow_up_date) {
            $score += 5;
        }

        if ($this->lead_temperature === 'hot') {
            $score += 20;
        }

        if ($this->lead_temperature === 'warm') {
            $score += 10;
        }

        if ($this->lead_priority === 'urgent') {
            $score += 20;
        }

        if ($this->lead_priority === 'high') {
            $score += 10;
        }

        if (in_array($this->customer_type, ['fleet', 'corporate'], true)) {
            $score += 10;
        }

        if ($this->retention_tag) {
            $score += 5;
        }

        if ($this->getMemoryValue('last_service_date')) {
            $score += 5;
        }

        if ($this->getMemoryValue('mulkia_expiry_date')) {
            $score += 5;
        }

        if (in_array($this->status, [self::STATUS_QUALIFIED, self::STATUS_CONVERTED], true)) {
            $score += 20;
        }

        if ($this->status === self::STATUS_LOST) {
            $score -= 40;
        }

        return max(0, min(100, $score));
    }

    public function isWhatsAppOrigin(): bool
    {
        return strtolower((string) $this->external_source) === 'whatsapp'
            || strtolower((string) $this->source) === 'whatsapp';
    }

    public function hasPhone(): bool
    {
        return ! empty($this->phone_norm) || ! empty($this->phone);
    }

    public function isOpen(): bool
    {
        return (bool) $this->is_active
            && in_array((string) $this->status, self::ACTIVE_STATUSES, true);
    }

    public function isConverted(): bool
    {
        return (string) $this->status === self::STATUS_CONVERTED;
    }

    public function isLost(): bool
    {
        return (string) $this->status === self::STATUS_LOST;
    }

    private function labelize(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return 'Unknown';
        }

        return ucwords(str_replace(['_', '-'], ' ', $value));
    }
}