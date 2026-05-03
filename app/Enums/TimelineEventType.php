<?php

namespace App\Enums;

final class TimelineEventType
{
    public const ENROLLMENT    = 'enrollment';
    public const STEP_DONE     = 'step_done';
    public const STEP_PENDING  = 'step_pending';
    public const AUTOMATION    = 'automation';
    public const WHATSAPP      = 'whatsapp';
    public const COMMUNICATION = 'communication';

    // Phase 9D: admin/user interventions show in timeline
    public const ACTION        = 'action';
}
