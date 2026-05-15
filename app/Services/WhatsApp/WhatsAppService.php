<?php

namespace App\Services\WhatsApp;

use App\Models\System\Company;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Services\WhatsApp\Drivers\MetaCloudWhatsApp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Twilio\Rest\Client as TwilioClient;

class WhatsAppService
{
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Availability Check
    |--------------------------------------------------------------------------
    */

    public function isActiveForCompany(int $companyId): bool
    {
        try {
            $provider = $this->getTenantProvider($companyId);

            if ($provider === 'meta') {
                return $this->hasMetaSettings($companyId);
            }

            if ($provider === 'twilio') {
                return $this->hasTwilioSettings($companyId);
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('[WA] isActiveForCompany failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Send Plain Text
    |--------------------------------------------------------------------------
    */

    public function sendText(string $toE164, string $body, array $context = []): array|bool
    {
        $companyId = $context['company_id'] ?? null;

        if (! $companyId) {
            throw new \Exception('WhatsAppService requires company_id');
        }

        $companyId = (int) $companyId;
        $provider = $this->getTenantProvider($companyId);
        $toE164 = $this->normalizeNumber($toE164);

        if (! $this->isActiveForCompany($companyId)) {
            throw new \Exception("WhatsApp is not configured or inactive for company {$companyId}");
        }

        return match ($provider) {
            'meta' => (new MetaCloudWhatsApp($companyId))
                ->sendText($toE164, $body),

            'twilio' => $this->sendTwilioText($companyId, $toE164, $body),

            default => throw new \Exception(
                "Unsupported WhatsApp provider: {$provider}"
            ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Send Template
    |--------------------------------------------------------------------------
    */

    public function sendTemplate(
        string $toE164,
        string $templateName,
        array $params = [],
        array $links = [],
        array $context = []
    ): array|bool {
        $companyId = $context['company_id'] ?? null;

        if (! $companyId) {
            throw new \Exception('WhatsAppService requires company_id');
        }

        $companyId = (int) $companyId;
        $provider = $this->getTenantProvider($companyId);
        $toE164 = $this->normalizeNumber($toE164);

        if (! $this->isActiveForCompany($companyId)) {
            throw new \Exception("WhatsApp is not configured or inactive for company {$companyId}");
        }

        return match ($provider) {
            'meta' => (new MetaCloudWhatsApp($companyId))
                ->sendTemplate($toE164, $templateName, $params),

            'twilio' => $this->sendTwilioTemplate(
                $companyId,
                $toE164,
                $templateName,
                $params,
                $links
            ),

            default => throw new \Exception(
                "Unsupported WhatsApp provider: {$provider}"
            ),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Manual Lead Welcome Helper
    |--------------------------------------------------------------------------
    */

    public function buildManualLeadWelcomeText(
        string $customerName,
        ?string $serviceType = null,
        ?string $vehicle = null
    ): string {
        $customerName = trim($customerName) ?: 'there';
        $serviceType = trim((string) $serviceType) ?: 'your service request';
        $vehicle = trim((string) $vehicle);

        $vehicleText = $vehicle !== ''
            ? " for your {$vehicle}"
            : '';

        return $this->assembleTemplateAsText('manual_lead_welcome_v1', [
            $customerName,
            $serviceType,
            $vehicleText,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | TWILIO TEXT
    |--------------------------------------------------------------------------
    */

    protected function sendTwilioText(
        int $companyId,
        string $to,
        string $body
    ): bool {
        $settings = $this->getTwilioSettings($companyId);

        $client = new TwilioClient(
            $settings['sid'],
            $settings['token']
        );

        $msg = $client->messages->create(
            $this->wa($to),
            [
                'from' => $this->wa($settings['from']),
                'body' => $body,
            ]
        );

        $this->logWa(
            'twilio',
            $to,
            'sent',
            $companyId,
            [
                'sid' => $msg->sid ?? null,
                'body' => $body,
            ]
        );

        return true;
    }

    /*
    |--------------------------------------------------------------------------
    | TWILIO TEMPLATE - Fallback As Text
    |--------------------------------------------------------------------------
    */

    protected function sendTwilioTemplate(
        int $companyId,
        string $to,
        string $template,
        array $params,
        array $links
    ): bool {
        $body = $this->assembleTemplateAsText(
            $template,
            $params,
            $links
        );

        return $this->sendTwilioText($companyId, $to, $body);
    }

    /*
    |--------------------------------------------------------------------------
    | Twilio Settings Loader
    |--------------------------------------------------------------------------
    */

    protected function getTwilioSettings(int $companyId): array
    {
        if (! Schema::hasTable('company_settings')) {
            throw new \Exception(
                "company_settings table not found. Twilio not configured for company {$companyId}"
            );
        }

        $sid = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->whereIn('key', ['twilio.sid', 'twilio_sid'])
            ->value('value');

        $token = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->whereIn('key', ['twilio.token', 'twilio_token'])
            ->value('value');

        $from = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->whereIn('key', [
                'twilio.whatsapp_from',
                'twilio_whatsapp_from',
                'whatsapp.from',
                'whatsapp_from',
            ])
            ->value('value');

        if (! $sid || ! $token || ! $from) {
            throw new \Exception(
                "Twilio not configured for company {$companyId}"
            );
        }

        return compact('sid', 'token', 'from');
    }

    protected function hasTwilioSettings(int $companyId): bool
    {
        try {
            $settings = $this->getTwilioSettings($companyId);

            return ! empty($settings['sid'])
                && ! empty($settings['token'])
                && ! empty($settings['from']);
        } catch (\Throwable) {
            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Meta Settings Loader
    |--------------------------------------------------------------------------
    */

    protected function hasMetaSettings(int $companyId): bool
    {
        $company = Company::query()->find($companyId);

        if ($company) {
            return filled($company->meta_phone_number_id)
                && filled($company->meta_access_token)
                && (bool) ($company->is_whatsapp_active ?? false);
        }

        if (! Schema::hasTable('company_settings')) {
            return false;
        }

        $keys = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->whereIn('key', [
                'meta.phone_number_id',
                'meta_phone_number_id',
                'meta.access_token',
                'meta_access_token',
                'meta.waba_id',
                'meta_waba_id',
                'whatsapp.phone_number_id',
                'whatsapp_phone_number_id',
                'whatsapp.access_token',
                'whatsapp_access_token',
                'whatsapp.active',
                'whatsapp_active',
            ])
            ->pluck('value', 'key')
            ->toArray();

        $phoneNumberId =
            $keys['meta.phone_number_id']
            ?? $keys['meta_phone_number_id']
            ?? $keys['whatsapp.phone_number_id']
            ?? $keys['whatsapp_phone_number_id']
            ?? null;

        $accessToken =
            $keys['meta.access_token']
            ?? $keys['meta_access_token']
            ?? $keys['whatsapp.access_token']
            ?? $keys['whatsapp_access_token']
            ?? null;

        $active =
            $keys['whatsapp.active']
            ?? $keys['whatsapp_active']
            ?? '1';

        return ! empty($phoneNumberId)
            && ! empty($accessToken)
            && (string) $active !== '0';
    }

    /*
    |--------------------------------------------------------------------------
    | TEMPLATE LIBRARY - Sandbox / Session Fallback
    |--------------------------------------------------------------------------
    */

    public function assembleTemplateAsText(
        string $template,
        array $params = [],
        array $links = []
    ): string {
        $library = [

            /*
            |--------------------------------------------------------------------------
            | Manual Lead Journey
            |--------------------------------------------------------------------------
            | {0} = customer name
            | {1} = tentative service type
            | {2} = vehicle text, example: " for your Nissan Patrol"
            |--------------------------------------------------------------------------
            */

            'manual_lead_welcome_v1' =>
                "Hi {0} 👋\n\n" .
                "Thank you for contacting us regarding {1}{2}.\n\n" .
                "Please share your preferred appointment date and time, and we will help you book a suitable slot.",

            'manual_lead_follow_up_v1' =>
                "Hi {0}, just following up on your {1} request{2}.\n\n" .
                "Would you like us to help you book an appointment?",

            'manual_lead_booking_push_v1' =>
                "Hi {0}, we can help you with {1}{2}.\n\n" .
                "Please reply with your preferred date and time so we can arrange a booking.",

            /*
            |--------------------------------------------------------------------------
            | Greeting / Intent
            |--------------------------------------------------------------------------
            */

            'ask_intent_v1' =>
                "👋 Hi {0}!\n\n" .
                "How can we help today?\n\n" .
                "1️⃣ Book a service\n" .
                "2️⃣ General enquiry\n" .
                "3️⃣ Speak to a manager",

            'gratitude_v1' =>
                "You're welcome, {0}! 😊\n\n" .
                "Let us know if you need anything else.",

            /*
            |--------------------------------------------------------------------------
            | Pricing / General Enquiry
            |--------------------------------------------------------------------------
            */

            'pricing_handoff_v1' =>
                "Thanks {0}! Pricing depends on the vehicle and service required.\n\n" .
                "Our service manager will check and share the best estimate shortly.",

            'ask_general_enquiry_v1' =>
                "Sure {0}. Please tell us your question or requirement.\n\n" .
                "Example:\nAC issue, pickup/drop, service cost, location, timing, warranty, or any other query.",

            'general_enquiry_handoff_v1' =>
                "Thanks {0}. We have shared your enquiry with our service manager.\n\n" .
                "They will contact you shortly to assist.",

            'ask_service_type_v1' =>
                "Sure {0}. What service do you need?\n\n" .
                "Example:\nOil change, AC repair, brake service, general service",

            /*
            |--------------------------------------------------------------------------
            | Booking Flow
            |--------------------------------------------------------------------------
            */

            'ask_make_model_v1' =>
                "🚗 Please tell us your vehicle make and model.\n\n" .
                "Example:\nToyota Camry",

            'ask_preferred_time_v1' =>
                "📅 Thanks {0}! What date/time works best?",

            'confirm_booking_v1' =>
                "📅 Please confirm your booking request.\n\n" .
                "Vehicle: {0}\n" .
                "Preferred date/time: {1}\n\n" .
                "Reply *Yes* to confirm or *No* to change.",

            'booking_confirmed_v1' =>
                "Booking confirmed ✅\n\n" .
                "Ref: {0}\n" .
                "Date: {1}\n" .
                "Time: {2}",

            'booking_already_created_v1' =>
                "✅ Your booking request is already captured.\n\n" .
                "Our team will review and confirm shortly.",

            'ask_preferred_time_retry_v1' =>
                "Hi {0},\n\n" .
                "{1}",

            /*
            |--------------------------------------------------------------------------
            | Escalations / Handoff
            |--------------------------------------------------------------------------
            */

            'manager_handoff_v1' =>
                "✅ Thanks! Our service manager will contact you shortly.",

            'booking_handoff_v1' =>
                "✅ Thanks {0}. Your booking request has been shared with our service manager.\n\n" .
                "They will contact you shortly to confirm the slot.",

            'lead_acknowledgment_v2' =>
                "Hi 👋 thanks for contacting us.\n" .
                "Our manager will reach out shortly.",

            'visit_handoff_v1' =>
                "Got it! Our manager will reach out shortly.",

            'manager_call_lead' =>
                "Lead alert 👤\n" .
                "Name: {0}\n" .
                "Phone: {1}\n" .
                "Source: {2}\n" .
                "Reason: {3}",

            /*
            |--------------------------------------------------------------------------
            | Retention / Future Buckets
            |--------------------------------------------------------------------------
            */

            'retention_general_service_due_v1' =>
                "Hi {0}, hope your {1} is running well.\n\n" .
                "Your last general service was around {2}. It may be time for the next service check.\n\n" .
                "Would you like to book a slot?",

            'retention_oil_change_due_v1' =>
                "Hi {0}, your {1} may be due for an oil change soon.\n\n" .
                "Would you like us to help schedule a quick service appointment?",

            'retention_ac_service_due_v1' =>
                "Hi {0}, with UAE weather, it is a good time to check your car AC.\n\n" .
                "Would you like to book an AC inspection for your {1}?",

            'retention_mulkia_renewal_v1' =>
                "Hi {0}, your vehicle registration renewal is coming up soon for your {1}.\n\n" .
                "We can help inspect the car and make it ready for renewal inspection.\n\n" .
                "Would you like to book a check-up?",

            /*
            |--------------------------------------------------------------------------
            | Fallback / Errors
            |--------------------------------------------------------------------------
            */

            'fallback_v1' =>
                "Sorry, I couldn't understand that clearly.\n\n" .
                "Please reply with:\n" .
                "1️⃣ Book a service\n" .
                "2️⃣ General enquiry\n" .
                "3️⃣ Speak to a manager",

            'system_error_handoff_v1' =>
                "Sorry, something went wrong while processing your request.\n\n" .
                "Our service manager will contact you shortly.",

            /*
            |--------------------------------------------------------------------------
            | Future Campaign / CRM Templates
            |--------------------------------------------------------------------------
            */

            'lead_conversation_start_v1' =>
                "Hi {0} 👋\n\n" .
                "Thanks for contacting us. How can we help you today?\n\n" .
                "1️⃣ Book a service\n" .
                "2️⃣ General enquiry\n" .
                "3️⃣ Speak to a manager\n\n" .
                "Please reply with 1, 2, or 3.",

            'follow_up_v1' =>
                "Hi {0}, just following up on your service request.\n\n" .
                "Would you like us to help you book a slot?",

            'feedback_request_v1' =>
                "Hi {0}, thank you for choosing us.\n\n" .
                "Please share your feedback about your service experience.",
        ];

        $body = $library[$template] ?? "Template: {$template}";

        foreach ($params as $i => $val) {
            $body = str_replace('{'.$i.'}', (string) $val, $body);
        }

        if (! empty($links)) {
            foreach ($links as $url) {
                $body .= "\n" . $url;
            }
        }

        return trim($body);
    }

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */

    protected function logWa(
        string $provider,
        string $to,
        string $status,
        ?int $companyId = null,
        array $payload = []
    ): void {
        try {
            WhatsAppMessage::create([
                'company_id' => $companyId,
                'to' => $to,
                'direction' => 'out',
                'status' => $status,
                'payload' => array_merge($payload, [
                    'provider' => $provider,
                ]),
            ]);
        } catch (\Throwable $e) {
            Log::error('[WA] logWa failed: '.$e->getMessage(), [
                'company_id' => $companyId,
                'to' => $to,
                'provider' => $provider,
                'status' => $status,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function wa(string $n): string
    {
        return Str::startsWith($n, 'whatsapp:')
            ? $n
            : 'whatsapp:' . trim($n);
    }

    public function normalizeNumber(?string $number): string
    {
        $number = trim((string) $number);
        $number = preg_replace('/^whatsapp:/i', '', $number);
        $number = preg_replace('/\D+/', '', $number);

        if (str_starts_with($number, '00')) {
            $number = substr($number, 2);
        }

        if (str_starts_with($number, '05')) {
            $number = '971' . substr($number, 1);
        }

        if (str_starts_with($number, '9710')) {
            $number = '971' . substr($number, 3);
        }

        return $number;
    }

    /*
    |--------------------------------------------------------------------------
    | Tenant Provider Resolver
    |--------------------------------------------------------------------------
    */

    protected function getTenantProvider(int $companyId): string
    {
        if (! Schema::hasTable('company_settings')) {
            return 'meta';
        }

        $provider = DB::table('company_settings')
            ->where('company_id', $companyId)
            ->whereIn('key', [
                'whatsapp.provider',
                'wa.provider',
                'meta.provider',
                'provider.whatsapp',
            ])
            ->value('value');

        $provider = strtolower(trim((string) $provider));

        if (! $provider) {
            return 'meta';
        }

        return $provider;
    }
}