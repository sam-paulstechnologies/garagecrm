<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimelineComment extends Model
{
    protected $fillable = [
        'company_id',
        'entity_type',
        'entity_id',
        'actor_type',
        'actor_id',
        'comment',
    ];

    public static function system(
        int $companyId,
        string $entityType,
        int $entityId,
        string $comment
    ): void {
        self::create([
            'company_id'  => $companyId,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'actor_type'  => 'system',
            'comment'     => $comment,
        ]);
    }
}
