<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class MessageLog
{
    public static function out(array $data): void
    {
        DB::table('message_logs')->insert(array_merge([
            'direction'  => 'out',
            'channel'    => 'whatsapp',
            'created_at' => now(),
            'updated_at' => now(),
        ], $data));
    }

    public static function in(array $data): void
    {
        DB::table('message_logs')->insert(array_merge([
            'direction'  => 'in',
            'channel'    => 'whatsapp',
            'created_at' => now(),
            'updated_at' => now(),
        ], $data));
    }
}
