<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Jobs\DispatchNotificationJob;

class SendUnifiedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public $queue = 'notifications';
    public $tries = 3;
    public $timeout = 120;

    /**
     * Handle ANY of the mapped events. We just enqueue a job with the raw event.
     */
    public function handle(object $event): void
    {
        // The job will read config/notify.php and do the sending + logging.
        DispatchNotificationJob::dispatch($event)->onQueue($this->queue);
    }
}

