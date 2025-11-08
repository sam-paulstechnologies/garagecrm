<?php

namespace App\Events;

use App\Models\MessageLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public MessageLog $message) {}
}
