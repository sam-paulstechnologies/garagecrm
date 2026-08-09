<?php

namespace App\Commercial;

final class Plans
{
    public const FREE = 'free';

    public const SERVICE = 'service';

    public const GROWTH = 'growth';

    public const PERFORMANCE = 'performance';

    public const AI_PRO = 'ai_pro';

    /** @return list<string> */
    public static function codes(): array
    {
        return [self::FREE, self::SERVICE, self::GROWTH, self::PERFORMANCE, self::AI_PRO];
    }
}
