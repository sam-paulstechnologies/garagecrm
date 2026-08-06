<?php

namespace App\Messaging\Enums;

enum ConnectionMode: string
{
    case BusinessApp = 'business_app_onboarding';
    case CloudApi = 'cloud_api';
}
