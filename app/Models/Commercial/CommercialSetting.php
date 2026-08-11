<?php

namespace App\Models\Commercial;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialSetting extends Model
{
    protected $fillable = ['key', 'boolean_value', 'updated_by'];

    protected $casts = ['boolean_value' => 'boolean'];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
