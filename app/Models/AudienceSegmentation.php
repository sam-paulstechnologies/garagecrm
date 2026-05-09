<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AudienceSegmentation extends Model
{
    use HasFactory;

    protected $table = 'audience_segmentations';

    protected $fillable = [
        'key',
        'name',
        'category',
        'description',
        'audience_rule_description',
        'trigger_description',
        'message_description',
        'example_message',
        'trigger_event',
        'trigger_delay_value',
        'trigger_delay_unit',
        'is_system_defined',
        'default_enabled',
        'sort_order',
    ];

    protected $casts = [
        'is_system_defined' => 'boolean',
        'default_enabled' => 'boolean',
        'trigger_delay_value' => 'integer',
        'sort_order' => 'integer',
    ];

    public function companySettings()
    {
        return $this->hasMany(CompanyAudienceSegmentationSetting::class, 'audience_segmentation_id');
    }

    public function settingForCompany(int $companyId)
    {
        return $this->companySettings()
            ->where('company_id', $companyId)
            ->first();
    }

    public function isEnabledForCompany(int $companyId): bool
    {
        $setting = $this->settingForCompany($companyId);

        if (! $setting) {
            return (bool) $this->default_enabled;
        }

        return (bool) $setting->is_enabled;
    }

    public function getTriggerTimingLabelAttribute(): string
    {
        if ($this->trigger_delay_value === null || $this->trigger_delay_unit === null) {
            return 'Based on trigger condition';
        }

        if ((int) $this->trigger_delay_value === 0) {
            return 'Immediately';
        }

        return $this->trigger_delay_value . ' ' . $this->trigger_delay_unit;
    }
}