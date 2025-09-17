<?php

namespace App\Jobs;

use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppTemplateJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $toE164,
        public string $template,
        public array $params = [],
        public array $links = [],
        public array $context = []
    ) {}

    public function handle(WhatsAppService $wa): void
    {
        $wa->sendTemplate($this->toE164, $this->template, $this->params, $this->links, $this->context);
    }
}
