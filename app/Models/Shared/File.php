<?php

namespace App\Models\Shared;

use App\Models\Client\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory;

    /** If your table does NOT have created_at/updated_at, keep timestamps off */
    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'file_name',
        'file_path',
        'file_type',
        'uploaded_at',      // datetime of upload
        // 'uploaded_by',    // optional FK to users.id (include if column exists)
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    /* -------------------------
     | Relationships
     ------------------------- */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Optional: who uploaded the file.
     * Safe even if 'uploaded_by' column is absent (will just return the default).
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')
            ->withDefault(['name' => 'Unknown']);
    }

    /* -------------------------
     | Scopes
     ------------------------- */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->orderByDesc('uploaded_at')->limit($limit);
    }
}
