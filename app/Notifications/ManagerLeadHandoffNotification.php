<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ManagerLeadHandoffNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int    $companyId,
        public int    $leadId,
        public string $name,
        public string $phone,
        public string $source,
        public string $reason
    ) {
        $this->onConnection('database');
        $this->onQueue('default');
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'       => 'lead_handoff',
            'company_id' => $this->companyId,
            'lead_id'    => $this->leadId,
            'title'      => 'Lead needs attention',
            'message'    => "{$this->name} ({$this->phone}) replied. Source: {$this->source}.",
            'reason'     => $this->reason,
            'url'        => route('admin.leads.show', $this->leadId),
        ];
    }
}
