<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use App\Mail\GenericEmail;
use App\Models\AutomationRule;
use App\Models\Template;
use Twilio\Rest\Client;

class CommunicationService
{
    protected $twilioClient;

    public function __construct()
    {
        $this->twilioClient = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
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
        if ($templateId) {
            $template = Template::find($templateId);
            $message = $this->applyTemplate($template, $message);
        }

        $this->twilioClient->messages->create(
            "whatsapp:$to",
            [
                'from' => "whatsapp:" . env('TWILIO_WHATSAPP_FROM'),
                'body' => $message
            ]
        );
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