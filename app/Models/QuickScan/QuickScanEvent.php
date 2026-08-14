<?php

namespace App\Models\QuickScan;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuickScanEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['quick_scan_workspace_id', 'actor_id', 'event', 'context', 'created_at'];

    protected $casts = ['context' => 'array', 'created_at' => 'datetime'];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(QuickScanWorkspace::class, 'quick_scan_workspace_id');
    }
}
