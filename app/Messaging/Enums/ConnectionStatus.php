<?php

namespace App\Messaging\Enums;

enum ConnectionStatus: string
{
    case Pending = 'pending';
    case Authorizing = 'authorizing';
    case DiscoveringAssets = 'discovering_assets';
    case AssigningAccess = 'assigning_access';
    case RegisteringPhone = 'registering_phone';
    case Subscribing = 'subscribing';
    case Verifying = 'verifying';
    case Connected = 'connected';
    case RequiresAction = 'requires_action';
    case Failed = 'failed';
    case Disconnected = 'disconnected';
}
