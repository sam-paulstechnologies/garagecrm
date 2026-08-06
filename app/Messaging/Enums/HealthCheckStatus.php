<?php

namespace App\Messaging\Enums;

enum HealthCheckStatus: string
{
    case Passed = 'passed';
    case Warning = 'warning';
    case Failed = 'failed';
    case Pending = 'pending';
}
