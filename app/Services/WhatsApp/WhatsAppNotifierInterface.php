<?php

namespace App\Services\WhatsApp;

interface WhatsAppNotifierInterface
{
    public function sendText(string $toE164, string $message): bool;
    public function sendTemplate(string $toE164, string $template, array $variables = []): bool;
}
