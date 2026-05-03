<?php

namespace App\Enums;

enum OpportunityStatus: string
{
    case NEW = 'new';
    case DETAILS_IN_PROGRESS = 'details_in_progress';
    case READY_FOR_BOOKING = 'ready_for_booking';
}
