<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudienceRule extends Model
{
    protected $fillable = ['audience_id', 'rules_json'];

    protected $casts = [
        'rules_json' => 'array',
    ];

    public function audience(): BelongsTo
    {
        return $this->belongsTo(Audience::class);
    }
}
