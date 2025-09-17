<?php

namespace App\Models\Client;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    // Always eager-load the author so views can show the name without extra queries
    protected $with = ['creator'];

    protected $fillable = [
        'company_id',
        'client_id',
        'content',
        'created_by',   // FK -> users.id
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* -------------------------
     | Relationships
     ------------------------- */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Author of the note (user who created it).
     * Uses withDefault so $note->creator->name is always safe to access.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')
            ->withDefault(['name' => 'Unknown']);
    }
}
