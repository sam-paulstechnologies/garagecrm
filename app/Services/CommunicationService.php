<?php

namespace App\Services;

use App\Mail\GenericEmail;
use App\Models\AutomationRule;
use App\Models\Template;
use App\Support\Staging\StagingSafety;
use Illuminate\Support\Facades\Mail;
use Twilio\Rest\Client;

class CommunicationService
{
    protected $twilioClient;

    public function __construct()
    {
        $this->twilioClient = new Client(
            (string) config('services.twilio.sid'),
            (string) config('services.twilio.token'),
        );
    }

    public function sendEmail($to, $subject, $body, $templateId = null)
    {
        if ($templateId) {
            $template = Template::find($templateId);
            $body = $this->applyTemplate($template, $body);
        }

        Mail::to($to)->send(new GenericEmail($subject, $body));
    }

    public function sendWhatsApp($to, $message, $templateId = null)
    {
        app(StagingSafety::class)->assertWhatsAppOutboundAllowed((string) $to);

        if ($templateId) {
            $template = Template::find($templateId);
            $message = $this->applyTemplate($template, $message);
        }

        $this->twilioClient->messages->create(
            "whatsapp:$to",
            [
                'from' => $this->whatsappAddress((string) config('services.twilio.whatsapp_from')),
                'body' => $message,
            ]
        );
    }

    private function whatsappAddress(string $number): string
    {
        return str_starts_with($number, 'whatsapp:') ? $number : 'whatsapp:'.$number;
    }

    public function applyTemplate($template, $content)
    {
        // Assuming templates have placeholders like {{name}}, {{date}}, etc.
        foreach ($template->placeholders as $placeholder => $value) {
            $content = str_replace("{{{$placeholder}}}", $value, $content);
        }

        return $content;
    }

    public function handleAutomation($event)
    {
        $rules = AutomationRule::where('event', $event)->get();

        foreach ($rules as $rule) {
            if ($rule->action_type == 'email') {
                $this->sendEmail($rule->recipient, $rule->subject, $rule->message, $rule->template_id);
            } elseif ($rule->action_type == 'whatsapp') {
                $this->sendWhatsApp($rule->recipient, $rule->message, $rule->template_id);
            }
        }
    }
}
