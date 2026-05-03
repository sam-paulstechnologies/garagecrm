<?php

namespace App\Services\Conversation;

use App\Models\Client\Lead;

class IntentResolver
{
    public function resolve(string $text): string
    {
        $t = $this->normalize($text);

        if ($t === '') {
            return 'general_enquiry';
        }

        /*
        |--------------------------------------------------------------------------
        | Quick reply buttons
        |--------------------------------------------------------------------------
        */

        if ($t === '1' || str_contains($t, '1. book') || str_contains($t, 'book a service')) {
            return 'booking';
        }

        if ($t === '2' || str_contains($t, '2. general') || str_contains($t, 'general enquiry')) {
            return 'general_enquiry';
        }

        if ($t === '3' || str_contains($t, '3. speak') || str_contains($t, 'speak to a manager')) {
            return 'manager';
        }

        /*
        |--------------------------------------------------------------------------
        | Gratitude
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(thanks|thank you|thx|appreciate|appreciated)\b/i', $t)) {
            return 'gratitude';
        }

        /*
        |--------------------------------------------------------------------------
        | Manager / human
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(manager|human|agent|call me|callback|call back|representative|advisor|supervisor)\b/i', $t)) {
            return 'manager';
        }

        /*
        |--------------------------------------------------------------------------
        | Complaint
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(complaint|issue|problem|not happy|bad service|angry|upset|delay|delayed|poor service)\b/i', $t)) {
            return 'complaint';
        }

        /*
        |--------------------------------------------------------------------------
        | Emergency
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(emergency|urgent|breakdown|tow|towing|stuck|not starting|car stopped|accident)\b/i', $t)) {
            return 'emergency';
        }

        /*
        |--------------------------------------------------------------------------
        | Booking / service intent
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(book|booking|appointment|schedule|slot|service|servicing|repair|maintenance|fix|check|inspection|pickup|pick up)\b/i', $t)) {
            return 'booking';
        }

        /*
        |--------------------------------------------------------------------------
        | Price / quotation
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(price|pricing|cost|quote|quotation|charges|how much|estimate|rate|rates)\b/i', $t)) {
            return 'general_enquiry';
        }

        /*
        |--------------------------------------------------------------------------
        | General enquiry
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(query|question|general|enquiry|inquiry|location|timing|time|warranty|drop|available|availability|open|close|closing)\b/i', $t)) {
            return 'general_enquiry';
        }

        /*
        |--------------------------------------------------------------------------
        | Greeting
        |--------------------------------------------------------------------------
        */

        if (preg_match('/\b(hi|hello|hey|good morning|good afternoon|good evening)\b/i', $t)) {
            return 'greeting';
        }

        return 'general_enquiry';
    }

    public function captureServiceType(Lead $lead, string $text): void
    {
        $t = $this->normalize($text);

        if ($t === '') {
            return;
        }

        $serviceType = $this->detectServiceType($t);

        if (!$serviceType) {
            return;
        }

        $data = $lead->conversation_data ?? [];

        if (!is_array($data)) {
            $data = [];
        }

        $isCorrection = preg_match('/\b(no|not|actually|change|instead|rather)\b/i', $t);

        if (!empty($data['service_type']) && !$isCorrection) {
            return;
        }

        $data['service_type'] = $serviceType;
        $data['service_type_captured_at'] = now()->toIso8601String();
        $data['service_type_source_text'] = $text;

        $lead->conversation_data = $data;
        $lead->save();
    }

    protected function detectServiceType(string $text): ?string
    {
        if (preg_match('/\b(oil|engine oil|oil change|lube)\b/i', $text)) {
            return 'Oil Change';
        }

        if (preg_match('/\b(brake|brakes|brake pad|brake pads|disc|rotor)\b/i', $text)) {
            return 'Brake Service';
        }

        if (preg_match('/\b(tyre|tyres|tire|tires|wheel|puncture|alignment|balancing)\b/i', $text)) {
            return 'Tyre Service';
        }

        if (preg_match('/\b(battery|jump start|jumpstart|not starting)\b/i', $text)) {
            return 'Battery Service';
        }

        if (preg_match('/\b(ac|a\/c|air condition|air conditioning|cooling|not cooling)\b/i', $text)) {
            return 'AC Repair';
        }

        if (preg_match('/\b(engine|overheat|overheating|noise|leak|leaking|diagnostic|diagnosis)\b/i', $text)) {
            return 'Mechanical Repair';
        }

        if (preg_match('/\b(wash|detailing|polish|cleaning)\b/i', $text)) {
            return 'Car Wash / Detailing';
        }

        if (preg_match('/\b(service|servicing|maintenance|general service|full service|minor service|major service)\b/i', $text)) {
            return 'General Service';
        }

        return null;
    }

    protected function normalize(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}