<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyAudienceSegmentationSetting extends Model
{
    use HasFactory;

    protected $table = 'company_audience_segmentation_settings';

    protected $fillable = [
        'company_id',
        'audience_segmentation_id',
        'is_enabled',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'audience_segmentation_id' => 'integer',
        'is_enabled' => 'boolean',
    ];

    public function segmentation()
    {
        return $this->belongsTo(AudienceSegmentation::class, 'audience_segmentation_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}